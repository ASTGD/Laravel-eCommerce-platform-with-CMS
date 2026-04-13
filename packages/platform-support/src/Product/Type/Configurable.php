<?php

namespace Platform\PlatformSupport\Product\Type;

use Illuminate\Support\Str;
use Webkul\Product\Type\Configurable as BaseConfigurable;

class Configurable extends BaseConfigurable
{
    /**
     * Sync selected configurable attributes from the edit form after the native
     * Bagisto product update has completed.
     */
    public function update(array $data, $id, $attributes = [])
    {
        $product = $this->baseUpdate($data, $id, $attributes);

        if (! empty($attributes)) {
            return $product;
        }

        $this->syncEditableSuperAttributes($product, $data);

        $product->refresh()->load(['super_attributes', 'variants']);

        $baseFillableVariantAttributes = $this->attributeRepository
            ->findWhereIn('code', $this->fillableVariantAttributeCodes)
            ->unique('id')
            ->values();

        $previousVariantIds = $product->variants->pluck('id');

        foreach ($data['variants'] ?? [] as $variantId => $variantData) {
            $this->fillableVariantAttributes = collect($baseFillableVariantAttributes->all());

            foreach ($product->super_attributes as $superAttribute) {
                $this->fillableVariantAttributes->push($superAttribute);
            }

            $this->fillableVariantAttributes = $this->fillableVariantAttributes
                ->unique('id')
                ->values();

            if (Str::contains((string) $variantId, 'variant_')) {
                $superAttributes = [];

                foreach ($product->super_attributes as $superAttribute) {
                    if (! array_key_exists($superAttribute->code, $variantData)) {
                        continue 2;
                    }

                    $superAttributes[$superAttribute->id] = $variantData[$superAttribute->code];
                }

                $this->createVariant($product, $superAttributes, array_merge($variantData, [
                    'channel' => $data['channel'] ?? core()->getDefaultChannelCode(),
                    'locale' => $data['locale'] ?? core()->getDefaultLocaleCodeFromDefaultChannel(),
                ]));

                continue;
            }

            if (is_numeric($index = $previousVariantIds->search($variantId))) {
                $previousVariantIds->forget($index);
            }

            $this->updateVariant(array_merge($variantData, [
                'channel' => $data['channel'] ?? core()->getDefaultChannelCode(),
                'locale' => $data['locale'] ?? core()->getDefaultLocaleCodeFromDefaultChannel(),
                'tax_category_id' => $data['tax_category_id'] ?? null,
            ]), $variantId);
        }

        foreach ($previousVariantIds as $variantId) {
            $this->productRepository->delete($variantId);
        }

        return $product->refresh();
    }

    protected function syncEditableSuperAttributes($product, array $data): void
    {
        if (! array_key_exists('super_attribute_codes', $data)) {
            return;
        }

        $selectedCodes = collect($data['super_attribute_codes'])
            ->filter()
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->values();

        if ($selectedCodes->isEmpty()) {
            return;
        }

        $attributeIds = $product->attribute_family->configurable_attributes
            ->whereIn('code', $selectedCodes)
            ->pluck('id')
            ->values()
            ->all();

        if (empty($attributeIds)) {
            return;
        }

        $product->super_attributes()->sync($attributeIds);
    }

    protected function baseUpdate(array $data, $id, array $attributes = [])
    {
        $product = $this->productRepository->find($id);

        $product->update($data);

        if (! empty($attributes)) {
            $attributes = $this->attributeRepository->findWhereIn('code', $attributes);

            $this->attributeValueRepository->saveValues($data, $product, $attributes);

            return $product;
        }

        $this->attributeValueRepository->saveValues($data, $product, $product->attribute_family->custom_attributes);

        if (empty($data['channels'])) {
            $data['channels'][] = core()->getDefaultChannel()->id;
        }

        $product->channels()->sync($data['channels']);

        if (! isset($data['categories'])) {
            $data['categories'] = [];
        }

        $product->categories()->sync($data['categories']);

        $product->up_sells()->sync($data['up_sells'] ?? []);
        $product->cross_sells()->sync($data['cross_sells'] ?? []);
        $product->related_products()->sync($data['related_products'] ?? []);

        $this->productInventoryRepository->saveInventories($data, $product);
        $this->productImageRepository->upload($data, $product, 'images');
        $this->productVideoRepository->upload($data, $product, 'videos');
        $this->productCustomerGroupPriceRepository->saveCustomerGroupPrices($data, $product);

        return $product;
    }
}
