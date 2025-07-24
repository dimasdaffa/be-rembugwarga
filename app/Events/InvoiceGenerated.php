<?php

namespace App\Events;

use App\Models\Invoice; // Import
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {
        //
    }
}
