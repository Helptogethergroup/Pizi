<?php

namespace App\Notifications;

use App\Models\RentPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    use Queueable;

    public function __construct(public RentPayment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $tenantName = $this->payment->tenant?->name ?? 'Tenant';

        return [
            'type' => 'payment_received',
            'title' => '💰 Payment received',
            'message' => "{$tenantName} paid ₹" . number_format($this->payment->amount, 0) . ' via ' . strtoupper($this->payment->payment_method),
            'payment_id' => $this->payment->id,
            'url' => '/owner/rent/' . $this->payment->rent_bill_id,
            'icon' => '💰',
        ];
    }
}
