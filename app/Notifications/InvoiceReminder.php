<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class InvoiceReminder extends Notification
{
    use Queueable;

    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $period = Carbon::parse($this->invoice->period)->format('F Y');
        return [
            'invoice_id' => $this->invoice->id,
            'message' => "Pengingat: Tagihan iuran untuk periode {$period} akan jatuh tempo dalam 3 hari.",
        ];
    }
}
