<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\ShipmentRecord;
use Webkul\Sales\Models\Order;

class AdminOperationsDashboardService
{
    public function __construct(
        protected ManualToShipService $manualToShipService,
    ) {}

    public function executiveMetrics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $shipment = $this->shipmentOverview();
        $cod = $this->codOverview($startDate, $endDate);

        return [
            'to_ship' => [
                'current' => $shipment['to_ship']['count'],
                'secondary' => $shipment['ready_for_handover']['count'].' ready for handover',
                'progress' => null,
            ],

            'in_delivery' => [
                'current' => $shipment['in_delivery']['count'],
                'secondary' => $shipment['delivered_today']['count'].' delivered today',
                'progress' => null,
            ],

            'cod_receivable' => [
                'current' => $cod['receivable']['amount'],
                'formatted_total' => $cod['receivable']['formatted_amount'],
                'count' => $cod['receivable']['count'],
                'secondary' => $cod['active_orders']['count'].' active COD orders',
                'progress' => null,
            ],
        ];
    }

    public function operationsOverview(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        return [
            'shipment' => $this->shipmentOverview(),
            'cod' => $this->codOverview($startDate, $endDate),
        ];
    }

    public function shipmentTrend(Carbon $startDate, Carbon $endDate, array $intervalConfig): array
    {
        $created = $this->shipmentCountSeries('created_at', $startDate, $endDate, $intervalConfig);
        $handedOver = $this->shipmentCountSeries('handed_over_at', $startDate, $endDate, $intervalConfig);
        $delivered = $this->shipmentCountSeries('delivered_at', $startDate, $endDate, $intervalConfig);

        return [
            'labels' => collect($created)->pluck('label')->all(),
            'created' => collect($created)->pluck('total')->map(fn ($total) => (int) $total)->all(),
            'handed_over' => collect($handedOver)->pluck('total')->map(fn ($total) => (int) $total)->all(),
            'delivered' => collect($delivered)->pluck('total')->map(fn ($total) => (int) $total)->all(),
            'summary' => [
                'created' => (int) collect($created)->sum('total'),
                'handed_over' => (int) collect($handedOver)->sum('total'),
                'delivered' => (int) collect($delivered)->sum('total'),
            ],
        ];
    }

    public function attentionSummary(): array
    {
        if (! $this->hasTables(['shipment_records', 'cod_settlements'])) {
            return [
                'delivery_failed' => ['count' => 0],
                'requires_reattempt' => ['count' => 0],
                'cod_exceptions' => [
                    'count' => 0,
                    'amount' => 0.0,
                    'formatted_amount' => core()->formatBasePrice(0),
                ],
            ];
        }

        $codExceptionAmount = (float) DB::table('cod_settlements')
            ->whereIn('status', CodSettlement::exceptionStatuses())
            ->selectRaw('COALESCE(SUM(short_amount + disputed_amount), 0) as aggregate')
            ->value('aggregate');

        return [
            'delivery_failed' => [
                'count' => ShipmentRecord::query()
                    ->where('status', ShipmentRecord::STATUS_DELIVERY_FAILED)
                    ->count(),
            ],

            'requires_reattempt' => [
                'count' => ShipmentRecord::query()
                    ->where('requires_reattempt', true)
                    ->count(),
            ],

            'cod_exceptions' => [
                'count' => CodSettlement::query()
                    ->whereIn('status', CodSettlement::exceptionStatuses())
                    ->count(),
                'amount' => $codExceptionAmount,
                'formatted_amount' => core()->formatBasePrice($codExceptionAmount),
            ],
        ];
    }

    protected function shipmentOverview(): array
    {
        $queues = $this->toShipQueues();

        if (! $this->hasTables(['shipment_records'])) {
            return [
                'to_ship' => ['count' => $queues['needs_booking']],
                'ready_for_handover' => ['count' => $queues['ready_for_handover']],
                'in_delivery' => ['count' => 0],
                'handed_over_today' => ['count' => 0],
                'delivered_today' => ['count' => 0],
                'delivery_failed' => ['count' => 0],
                'requires_reattempt' => ['count' => 0],
            ];
        }

        return [
            'to_ship' => [
                'count' => $queues['needs_booking'],
            ],

            'ready_for_handover' => [
                'count' => $queues['ready_for_handover'],
            ],

            'in_delivery' => [
                'count' => ShipmentRecord::query()
                    ->whereIn('status', $this->inDeliveryStatuses())
                    ->count(),
            ],

            'handed_over_today' => [
                'count' => ShipmentRecord::query()
                    ->whereBetween('handed_over_at', [now()->startOfDay(), now()->endOfDay()])
                    ->count(),
            ],

            'delivered_today' => [
                'count' => ShipmentRecord::query()
                    ->whereBetween('delivered_at', [now()->startOfDay(), now()->endOfDay()])
                    ->count(),
            ],

            'delivery_failed' => [
                'count' => ShipmentRecord::query()
                    ->where('status', ShipmentRecord::STATUS_DELIVERY_FAILED)
                    ->count(),
            ],

            'requires_reattempt' => [
                'count' => ShipmentRecord::query()
                    ->where('requires_reattempt', true)
                    ->count(),
            ],
        ];
    }

    protected function codOverview(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = ($startDate ?? now()->subDays(30))->copy()->startOfDay();
        $endDate = ($endDate ?? now())->copy();

        if ($endDate->isFuture()) {
            $endDate = now();
        }

        if (! $this->hasTables(['orders', 'order_payment'])) {
            return $this->emptyCodOverview();
        }

        $codOrders = $this->codOrdersQuery($startDate, $endDate);
        $receivableOrders = (clone $codOrders)->whereNotIn('status', $this->excludedCodReceivableStatuses());

        $receivableAmount = (float) $receivableOrders
            ->selectRaw('COALESCE(SUM(GREATEST(COALESCE(base_grand_total, 0) - COALESCE(base_grand_total_refunded, 0), 0)), 0) as aggregate')
            ->value('aggregate');

        return [
            'receivable' => [
                'amount' => $receivableAmount,
                'formatted_amount' => core()->formatBasePrice($receivableAmount),
                'count' => (int) (clone $receivableOrders)->count(),
            ],

            'active_orders' => [
                'count' => (int) (clone $codOrders)
                    ->whereIn('status', $this->activeCodOrderStatuses())
                    ->count(),
            ],

            'shipped_orders' => [
                'count' => (int) (clone $codOrders)
                    ->where('status', Order::STATUS_SHIPPED)
                    ->count(),
            ],

            'completed_orders' => [
                'count' => (int) (clone $codOrders)
                    ->where('status', Order::STATUS_COMPLETED)
                    ->count(),
            ],

            'exceptions' => [
                'count' => (int) (clone $codOrders)
                    ->where(function (Builder $query) {
                        $query
                            ->whereIn('status', $this->codExceptionStatuses())
                            ->orWhere('base_grand_total_refunded', '>', 0);
                    })
                    ->count(),
            ],
        ];
    }

    protected function shipmentCountSeries(string $timestampColumn, Carbon $startDate, Carbon $endDate, array $intervalConfig): array
    {
        $intervals = $intervalConfig['intervals'] ?? [];

        if (! $this->hasTables(['shipment_records']) || empty($intervals)) {
            return $this->emptySeries($intervals);
        }

        $qualifiedColumn = 'shipment_records.'.$timestampColumn;
        $groupColumn = str_replace('created_at', $qualifiedColumn, $intervalConfig['group_column']);

        $results = DB::table('shipment_records')
            ->select(
                DB::raw("$groupColumn AS date"),
                DB::raw('COUNT(*) AS total'),
            )
            ->whereBetween($qualifiedColumn, [$startDate, $endDate])
            ->groupBy('date')
            ->get();

        return collect($intervals)
            ->map(function (array $interval) use ($results) {
                $total = $results->where('date', $interval['filter'])->first();

                return [
                    'label' => $interval['start'],
                    'total' => $total?->total ?? 0,
                ];
            })
            ->all();
    }

    protected function codOrdersQuery(Carbon $startDate, Carbon $endDate): Builder
    {
        return Order::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('payment', fn (Builder $query) => $query->where('method', 'cashondelivery'));
    }

    protected function toShipQueues(): array
    {
        if (! $this->hasTables(['orders', 'shipment_records'])) {
            return [
                'needs_booking' => 0,
                'ready_for_handover' => 0,
            ];
        }

        return $this->manualToShipService->queueCounts();
    }

    protected function emptySeries(array $intervals): array
    {
        return collect($intervals)
            ->map(fn (array $interval) => [
                'label' => $interval['start'],
                'total' => 0,
            ])
            ->all();
    }

    protected function inDeliveryStatuses(): array
    {
        return [
            ShipmentRecord::STATUS_HANDED_TO_CARRIER,
            ShipmentRecord::STATUS_IN_TRANSIT,
            ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
            ShipmentRecord::STATUS_DELIVERY_FAILED,
        ];
    }

    protected function activeCodOrderStatuses(): array
    {
        return [
            Order::STATUS_PENDING,
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
        ];
    }

    protected function excludedCodReceivableStatuses(): array
    {
        return $this->codExceptionStatuses();
    }

    protected function codExceptionStatuses(): array
    {
        return [
            Order::STATUS_CANCELED,
            Order::STATUS_CLOSED,
            Order::STATUS_FRAUD,
        ];
    }

    protected function emptyCodOverview(): array
    {
        return [
            'receivable' => [
                'amount' => 0.0,
                'formatted_amount' => core()->formatBasePrice(0),
                'count' => 0,
            ],
            'active_orders' => ['count' => 0],
            'shipped_orders' => ['count' => 0],
            'completed_orders' => ['count' => 0],
            'exceptions' => ['count' => 0],
        ];
    }

    protected function hasTables(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
