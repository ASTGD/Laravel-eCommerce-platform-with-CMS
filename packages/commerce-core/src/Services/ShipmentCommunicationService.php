<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Platform\CommerceCore\Mail\Admin\Shipment\OperationalUpdateNotification as AdminOperationalUpdateNotification;
use Platform\CommerceCore\Mail\Shop\Shipment\OperationalUpdateNotification as ShopOperationalUpdateNotification;
use Platform\CommerceCore\Models\ShipmentCommunication;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;
use Throwable;

class ShipmentCommunicationService
{
    public function dispatchForEventAfterCommit(int $shipmentRecordId, int $shipmentEventId): void
    {
        DB::afterCommit(function () use ($shipmentRecordId, $shipmentEventId): void {
            $this->dispatchForPersistedEvent($shipmentRecordId, $shipmentEventId);
        });
    }

    public function dispatchForPersistedEvent(int $shipmentRecordId, int $shipmentEventId): void
    {
        $shipmentRecord = ShipmentRecord::query()
            ->with([
                'order.items',
                'carrier',
                'events',
            ])
            ->find($shipmentRecordId);

        $shipmentEvent = ShipmentEvent::query()->find($shipmentEventId);

        if (! $shipmentRecord || ! $shipmentEvent) {
            return;
        }

        $notificationKey = $this->resolveNotificationKey($shipmentEvent);

        if (! $notificationKey) {
            return;
        }

        $this->dispatchCustomerNotification($shipmentRecord, $shipmentEvent, $notificationKey);
        $this->dispatchAdminNotification($shipmentRecord, $shipmentEvent, $notificationKey);
    }

    protected function dispatchCustomerNotification(ShipmentRecord $shipmentRecord, ShipmentEvent $shipmentEvent, string $notificationKey): void
    {
        $subject = $this->subjectForCustomer($shipmentRecord, $notificationKey);
        $recipientEmail = trim((string) $shipmentRecord->order?->customer_email);
        $recipientName = trim((string) $shipmentRecord->order?->customer_full_name);

        if (! $this->notificationEnabled(ShipmentCommunication::AUDIENCE_CUSTOMER, $notificationKey)) {
            $this->logCommunication($shipmentRecord, $shipmentEvent, [
                'audience' => ShipmentCommunication::AUDIENCE_CUSTOMER,
                'notification_key' => $notificationKey,
                'recipient_name' => $recipientName ?: null,
                'recipient_email' => $recipientEmail ?: null,
                'subject' => $subject,
                'status' => ShipmentCommunication::STATUS_SKIPPED,
                'reason' => 'Notification disabled in shipment notification settings.',
            ]);

            return;
        }

        if ($recipientEmail === '') {
            $this->logCommunication($shipmentRecord, $shipmentEvent, [
                'audience' => ShipmentCommunication::AUDIENCE_CUSTOMER,
                'notification_key' => $notificationKey,
                'subject' => $subject,
                'status' => ShipmentCommunication::STATUS_SKIPPED,
                'reason' => 'Customer email is missing on the order.',
            ]);

            return;
        }

        try {
            $mailable = (new ShopOperationalUpdateNotification($shipmentRecord, $shipmentEvent, $notificationKey, $subject))
                ->locale($this->resolveCustomerLocale($shipmentRecord));

            Mail::queue($mailable);

            $this->logCommunication($shipmentRecord, $shipmentEvent, [
                'audience' => ShipmentCommunication::AUDIENCE_CUSTOMER,
                'notification_key' => $notificationKey,
                'recipient_name' => $recipientName ?: null,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'status' => ShipmentCommunication::STATUS_QUEUED,
                'queued_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $this->logCommunication($shipmentRecord, $shipmentEvent, [
                'audience' => ShipmentCommunication::AUDIENCE_CUSTOMER,
                'notification_key' => $notificationKey,
                'recipient_name' => $recipientName ?: null,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'status' => ShipmentCommunication::STATUS_FAILED,
                'reason' => $exception->getMessage(),
                'failed_at' => now(),
            ]);
        }
    }

    protected function dispatchAdminNotification(ShipmentRecord $shipmentRecord, ShipmentEvent $shipmentEvent, string $notificationKey): void
    {
        $adminEmail = trim((string) data_get(core()->getAdminEmailDetails(), 'email'));
        $adminName = trim((string) data_get(core()->getAdminEmailDetails(), 'name'));
        $subject = $this->subjectForAdmin($shipmentRecord, $notificationKey);

        if (! $this->notificationEnabled(ShipmentCommunication::AUDIENCE_ADMIN, $notificationKey)) {
            $this->logCommunication($shipmentRecord, $shipmentEvent, [
                'audience' => ShipmentCommunication::AUDIENCE_ADMIN,
                'notification_key' => $notificationKey,
                'recipient_name' => $adminName ?: null,
                'recipient_email' => $adminEmail ?: null,
                'subject' => $subject,
                'status' => ShipmentCommunication::STATUS_SKIPPED,
                'reason' => 'Notification disabled in shipment notification settings.',
            ]);

            return;
        }

        if ($adminEmail === '') {
            $this->logCommunication($shipmentRecord, $shipmentEvent, [
                'audience' => ShipmentCommunication::AUDIENCE_ADMIN,
                'notification_key' => $notificationKey,
                'subject' => $subject,
                'status' => ShipmentCommunication::STATUS_SKIPPED,
                'reason' => 'Admin notification email is not configured.',
            ]);

            return;
        }

        try {
            Mail::queue(new AdminOperationalUpdateNotification($shipmentRecord, $shipmentEvent, $notificationKey, $subject));

            $this->logCommunication($shipmentRecord, $shipmentEvent, [
                'audience' => ShipmentCommunication::AUDIENCE_ADMIN,
                'notification_key' => $notificationKey,
                'recipient_name' => $adminName ?: null,
                'recipient_email' => $adminEmail,
                'subject' => $subject,
                'status' => ShipmentCommunication::STATUS_QUEUED,
                'queued_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $this->logCommunication($shipmentRecord, $shipmentEvent, [
                'audience' => ShipmentCommunication::AUDIENCE_ADMIN,
                'notification_key' => $notificationKey,
                'recipient_name' => $adminName ?: null,
                'recipient_email' => $adminEmail,
                'subject' => $subject,
                'status' => ShipmentCommunication::STATUS_FAILED,
                'reason' => $exception->getMessage(),
                'failed_at' => now(),
            ]);
        }
    }

    protected function resolveNotificationKey(ShipmentEvent $shipmentEvent): ?string
    {
        if ($shipmentEvent->event_type === ShipmentEvent::EVENT_RETURN_INITIATED) {
            return ShipmentCommunication::KEY_RETURN_INITIATED;
        }

        return match ($shipmentEvent->status_after_event) {
            ShipmentRecord::STATUS_OUT_FOR_DELIVERY => ShipmentCommunication::KEY_OUT_FOR_DELIVERY,
            ShipmentRecord::STATUS_DELIVERED => ShipmentCommunication::KEY_DELIVERED,
            ShipmentRecord::STATUS_DELIVERY_FAILED => ShipmentCommunication::KEY_DELIVERY_FAILED,
            ShipmentRecord::STATUS_RETURNED => ShipmentCommunication::KEY_RETURNED,
            default => null,
        };
    }

    protected function resolveCustomerLocale(ShipmentRecord $shipmentRecord): string
    {
        return (string) data_get($shipmentRecord->order?->items?->first(), 'additional.locale', 'en');
    }

    protected function notificationEnabled(string $audience, string $notificationKey): bool
    {
        return (bool) core()->getConfigData("sales.shipment_notifications.{$audience}_{$notificationKey}_email");
    }

    protected function subjectForCustomer(ShipmentRecord $shipmentRecord, string $notificationKey): string
    {
        $orderIncrementId = $shipmentRecord->order?->increment_id ?: $shipmentRecord->order_id;
        $label = ShipmentCommunication::notificationLabels()[$notificationKey]
            ?? str($notificationKey)->replace('_', ' ')->title()->value();

        return "{$label}: Order #{$orderIncrementId}";
    }

    protected function subjectForAdmin(ShipmentRecord $shipmentRecord, string $notificationKey): string
    {
        $orderIncrementId = $shipmentRecord->order?->increment_id ?: $shipmentRecord->order_id;
        $label = ShipmentCommunication::notificationLabels()[$notificationKey]
            ?? str($notificationKey)->replace('_', ' ')->title()->value();

        return "{$label}: Shipment Ops #{$shipmentRecord->id} / Order #{$orderIncrementId}";
    }

    protected function logCommunication(ShipmentRecord $shipmentRecord, ShipmentEvent $shipmentEvent, array $attributes): ShipmentCommunication
    {
        return ShipmentCommunication::query()->create([
            'shipment_record_id' => $shipmentRecord->id,
            'shipment_event_id' => $shipmentEvent->id,
            'channel' => ShipmentCommunication::CHANNEL_EMAIL,
            'meta' => [
                'event_type' => $shipmentEvent->event_type,
                'status_after_event' => $shipmentEvent->status_after_event,
                'tracking_number' => $shipmentRecord->tracking_number,
            ],
            ...$attributes,
        ]);
    }
}
