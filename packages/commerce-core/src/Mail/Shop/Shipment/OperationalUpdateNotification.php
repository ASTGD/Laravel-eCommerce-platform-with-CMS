<?php

namespace Platform\CommerceCore\Mail\Shop\Shipment;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Platform\CommerceCore\Models\ShipmentCommunication;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;
use Webkul\Shop\Mail\Mailable;

class OperationalUpdateNotification extends Mailable
{
    public function __construct(
        public ShipmentRecord $shipmentRecord,
        public ShipmentEvent $shipmentEvent,
        public string $notificationKey,
        public string $subjectLine,
        public string $notificationLabel = '',
    ) {
        $this->notificationLabel = ShipmentCommunication::notificationLabels()[$this->notificationKey]
            ?? str($this->notificationKey)->replace('_', ' ')->title()->value();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    $this->shipmentRecord->order->customer_email,
                    $this->shipmentRecord->order->customer_full_name
                ),
            ],
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'commerce-core::shop.emails.shipments.operational-update',
        );
    }
}
