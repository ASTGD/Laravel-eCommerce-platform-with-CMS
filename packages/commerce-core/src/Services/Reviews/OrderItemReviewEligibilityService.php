<?php

namespace Platform\CommerceCore\Services\Reviews;

use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Models\ShipmentRecordItem;
use Platform\CommerceCore\Support\AdminFeatureToggle;
use Webkul\Customer\Contracts\Customer;
use Webkul\Product\Models\ProductReview;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

class OrderItemReviewEligibilityService
{
    public function __construct(protected AdminFeatureToggle $adminFeatureToggle) {}

    public function reviewsEnabled(): bool
    {
        return $this->booleanValue(core()->getConfigData('catalog.products.review.customer_review'))
            && $this->adminFeatureToggle->enabled(AdminFeatureToggle::CUSTOMER_REVIEWS);
    }

    public function existingReviewForProduct(Customer|int|null $customer, int $productId): ?ProductReview
    {
        $customerId = $this->customerId($customer);

        if (! $customerId) {
            return null;
        }

        return ProductReview::query()
            ->where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->latest('id')
            ->first();
    }

    public function customerCanReviewProduct(Customer|int|null $customer, int $productId): bool
    {
        $customerId = $this->customerId($customer);

        if (! $this->reviewsEnabled() || ! $customerId || $this->existingReviewForProduct($customerId, $productId)) {
            return false;
        }

        return OrderItem::query()
            ->where('product_id', $productId)
            ->whereHas('order', fn ($query) => $query->where('customer_id', $customerId))
            ->with('order')
            ->get()
            ->contains(fn (OrderItem $item) => $this->itemHasReviewableDeliveryState($item));
    }

    public function canReviewOrderItem(OrderItem $item, Customer|int|null $customer): bool
    {
        $customerId = $this->customerId($customer);
        $productId = $this->reviewableProductId($item);

        if (! $this->reviewsEnabled() || ! $customerId || ! $productId) {
            return false;
        }

        if ((int) $item->order?->customer_id !== $customerId) {
            return false;
        }

        if ($this->existingReviewForProduct($customerId, $productId)) {
            return false;
        }

        return $this->itemHasReviewableDeliveryState($item);
    }

    public function stateForOrderItem(OrderItem $item, Customer|int|null $customer): array
    {
        $productId = $this->reviewableProductId($item);
        $existingReview = $productId ? $this->existingReviewForProduct($customer, $productId) : null;

        if ($existingReview) {
            return [
                'can_write' => false,
                'label' => $this->statusLabel($existingReview->status),
                'status' => $existingReview->status,
                'review_id' => $existingReview->id,
            ];
        }

        if ($this->canReviewOrderItem($item, $customer)) {
            return [
                'can_write' => true,
                'label' => trans('shop::app.customers.account.orders.view.review.write-review'),
                'status' => null,
                'review_id' => null,
            ];
        }

        return [
            'can_write' => false,
            'label' => null,
            'status' => null,
            'review_id' => null,
        ];
    }

    public function reviewableProductId(OrderItem $item): ?int
    {
        return $item->product_id ? (int) $item->product_id : null;
    }

    protected function itemHasReviewableDeliveryState(OrderItem $item): bool
    {
        $item->loadMissing('order');

        if (! $this->hasReviewableQuantity($item)) {
            return false;
        }

        if ($item->order?->status === Order::STATUS_COMPLETED) {
            return true;
        }

        return ShipmentRecordItem::query()
            ->where('order_item_id', $item->id)
            ->whereHas('shipmentRecord', fn ($query) => $query->where('status', ShipmentRecord::STATUS_DELIVERED))
            ->exists();
    }

    protected function hasReviewableQuantity(OrderItem $item): bool
    {
        return (float) $item->qty_ordered - (float) $item->qty_canceled - (float) $item->qty_refunded > 0;
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => trans('shop::app.customers.account.reviews.status.pending'),
            'approved' => trans('shop::app.customers.account.reviews.status.approved'),
            'disapproved' => trans('shop::app.customers.account.reviews.status.disapproved'),
            default => trans('shop::app.customers.account.reviews.status.submitted'),
        };
    }

    protected function customerId(Customer|int|null $customer): ?int
    {
        if ($customer instanceof Customer) {
            return (int) $customer->id;
        }

        return $customer ? (int) $customer : null;
    }

    protected function booleanValue(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }
}
