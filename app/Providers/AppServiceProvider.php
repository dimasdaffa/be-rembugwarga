<?php

namespace App\Providers;

use App\Events\InvoiceGenerated;
use App\Notifications\NewInvoice;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    // Daftarkan listener untuk kejadian InvoiceGenerated
    Event::listen(function (InvoiceGenerated $event) {
        // Ambil user dari relasi invoice
        $user = $event->invoice->user;

        // Kirim notifikasi ke user tersebut
        // Pastikan Anda menggunakan '$event->invoice', bukan '$invoice' saja
        if ($user) {
            $user->notify(new NewInvoice($event->invoice));
        }
    });
}
}
