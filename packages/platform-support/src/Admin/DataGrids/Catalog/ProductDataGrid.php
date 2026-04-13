<?php

namespace Platform\PlatformSupport\Admin\DataGrids\Catalog;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid as BaseProductDataGrid;
use Webkul\DataGrid\Enums\ColumnTypeEnum;

class ProductDataGrid extends BaseProductDataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = parent::prepareQueryBuilder();

        $queryBuilder
            ->leftJoin('products as catalog_products', 'product_flat.product_id', '=', 'catalog_products.id')
            ->addSelect('catalog_products.parent_id as parent_product_id')
            ->addSelect(DB::raw('(
                SELECT COUNT(*)
                FROM '.$tablePrefix.'products AS child_variants
                WHERE child_variants.parent_id = '.$tablePrefix.'product_flat.product_id
            ) AS variant_count'))
            ->whereNull('catalog_products.parent_id');

        return $queryBuilder;
    }

    /**
     * Process requested filters.
     */
    protected function processRequestedFilters(array $requestedFilters)
    {
        $this->dispatchEvent('process_request.filters.before', $this);

        foreach ($requestedFilters as $requestedColumn => $requestedValues) {
            if ($requestedColumn === 'all') {
                $this->applyParentAndVariantSearchFilter(['name', 'sku'], $requestedValues);

                continue;
            }

            if (in_array($requestedColumn, ['name', 'sku'], true)) {
                $this->applyParentAndVariantSearchFilter([$requestedColumn], $requestedValues);

                continue;
            }

            collect($this->columns)
                ->first(fn ($column) => $column->getIndex() === $requestedColumn)
                ->processFilter($this->queryBuilder, $requestedValues);
        }

        $this->dispatchEvent('process_request.filters.after', $this);
    }

    /**
     * Return applied filters for elastic search.
     */
    public function getElasticFilters($params): array
    {
        $filters = parent::getElasticFilters($params);

        $filters['must_not'][] = [
            'exists' => [
                'field' => 'parent_id',
            ],
        ];

        return $filters;
    }

    /**
     * Format records.
     */
    protected function formatRecords($records): mixed
    {
        $records = parent::formatRecords($records);

        if (empty($records)) {
            return $records;
        }

        $parentIds = collect($records)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($parentIds->isEmpty()) {
            return $records;
        }

        $channels = collect($records)
            ->pluck('channel')
            ->filter()
            ->unique()
            ->values();

        $variants = DB::table('product_flat as variants')
            ->join('products as variant_products', 'variants.product_id', '=', 'variant_products.id')
            ->leftJoin('product_inventories', 'variants.product_id', '=', 'product_inventories.product_id')
            ->leftJoin('product_images', 'variants.product_id', '=', 'product_images.product_id')
            ->select(
                'variant_products.parent_id as parent_product_id',
                'variants.channel',
                'variants.product_id',
                'variants.sku',
                'variants.name',
                'variants.status',
                'variants.price',
                'variants.type',
                'product_images.path as base_image'
            )
            ->addSelect(DB::raw('SUM(DISTINCT '.DB::getTablePrefix().'product_inventories.qty) as quantity'))
            ->addSelect(DB::raw('COUNT(DISTINCT '.DB::getTablePrefix().'product_images.id) as images_count'))
            ->whereIn('variant_products.parent_id', $parentIds->all())
            ->where('variants.locale', app()->getLocale())
            ->when(
                $channels->isNotEmpty(),
                fn ($query) => $query->whereIn('variants.channel', $channels->all())
            )
            ->groupBy('variants.product_id')
            ->orderBy('variant_products.parent_id')
            ->orderBy('variants.name')
            ->get()
            ->groupBy(fn ($variant) => $variant->parent_product_id.'|'.$variant->channel);

        foreach ($records as $record) {
            $variantRows = collect($variants->get($record->product_id.'|'.$record->channel, []))
                ->map(function ($variant) {
                    return [
                        'product_id'    => $variant->product_id,
                        'name'          => $variant->name,
                        'sku'           => $variant->sku,
                        'status'        => (bool) $variant->status,
                        'price'         => $variant->price,
                        'quantity'      => (int) ($variant->quantity ?? 0),
                        'images_count'  => (int) ($variant->images_count ?? 0),
                        'base_image'    => $variant->base_image ? Storage::url($variant->base_image) : null,
                        'edit_url'      => route('admin.catalog.products.edit', [
                            'id'      => $variant->product_id,
                            'channel' => $variant->channel,
                        ]),
                    ];
                })
                ->values()
                ->all();

            $record->variants = $variantRows;
            $record->variant_count = count($variantRows);
            $record->has_variants = $record->variant_count > 0;
            $record->is_expanded = false;
        }

        return $records;
    }

    /**
     * Apply search filters to parent products and their variants.
     */
    protected function applyParentAndVariantSearchFilter(array $columns, array $requestedValues): void
    {
        $searchableColumns = collect($this->columns)
            ->filter(function ($column) use ($columns) {
                return in_array($column->getIndex(), $columns, true)
                    && ! in_array($column->getType(), [
                        ColumnTypeEnum::BOOLEAN->value,
                        ColumnTypeEnum::AGGREGATE->value,
                    ], true);
            })
            ->values();

        if ($searchableColumns->isEmpty()) {
            return;
        }

        $this->queryBuilder->where(function ($scopeQueryBuilder) use ($requestedValues, $searchableColumns) {
            foreach ($requestedValues as $value) {
                $scopeQueryBuilder->orWhere(function ($matchQuery) use ($value, $searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $matchQuery->orWhere($this->qualifyParentColumn($column->getColumnName()), 'LIKE', '%'.$value.'%');
                    }

                    $matchQuery->orWhereExists(function ($variantQuery) use ($value, $searchableColumns) {
                        $variantQuery
                            ->select(DB::raw(1))
                            ->from('product_flat as variant_search')
                            ->join('products as variant_search_products', 'variant_search.product_id', '=', 'variant_search_products.id')
                            ->whereColumn('variant_search_products.parent_id', 'product_flat.product_id')
                            ->whereColumn('variant_search.locale', 'product_flat.locale')
                            ->whereColumn('variant_search.channel', 'product_flat.channel')
                            ->where(function ($variantMatchQuery) use ($value, $searchableColumns) {
                                foreach ($searchableColumns as $column) {
                                    $columnName = str_replace(
                                        'product_flat.',
                                        'variant_search.',
                                        $this->qualifyParentColumn($column->getColumnName())
                                    );

                                    $variantMatchQuery->orWhere($columnName, 'LIKE', '%'.$value.'%');
                                }
                            });
                    });
                });
            }
        });
    }

    /**
     * Qualify parent datagrid columns.
     */
    protected function qualifyParentColumn(string $columnName): string
    {
        return str_contains($columnName, '.')
            ? $columnName
            : 'product_flat.'.$columnName;
    }
}
