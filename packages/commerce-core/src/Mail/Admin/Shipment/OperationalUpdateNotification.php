<?php

namespace Platform\CommerceCore\Mail\Admin\Shipment;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Platform\CommerceCore\Models\ShipmentCommunication;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;
use Webkul\Admin\Mail\Mailable;

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
                    core()->getAdminEmailDetails()['email'],
                    core()->getAdminEmailDetails()['name']
                ),
            ],
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'commerce-core::admin.emails.shipments.operational-update',
        );
    }
}
