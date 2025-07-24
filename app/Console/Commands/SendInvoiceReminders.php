<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\InvoiceReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendInvoiceReminders extends Command
{
    protected $signature = 'send:reminders';
    protected $description = 'Kirim notifikasi pengingat untuk tagihan yang akan jatuh tempo';

    public function handle()
    {
        $this->info('Mulai mengirim pengingat tagihan...');

        // Cari semua tagihan yang statusnya 'pending' DAN
        // akan jatuh tempo tepat 3 hari dari sekarang.
        $reminderDate = Carbon::now()->addDays(3)->toDateString();

        $invoices = Invoice::with('user')
            ->where('status', 'pending')
            ->where('due_date', $reminderDate)
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('Tidak ada tagihan yang perlu diingatkan hari ini.');
            return;
        }

        foreach ($invoices as $invoice) {
            $invoice->user->notify(new InvoiceReminder($invoice));
            $this->info("Pengingat terkirim ke: {$invoice->user->name} untuk tagihan #{$invoice->id}");
        }

        $this->info('Selesai mengirim pengingat.');
    }
}
