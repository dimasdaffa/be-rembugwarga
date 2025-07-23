<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Warga yang ditagih
            $table->decimal('amount', 10, 2); // Jumlah tagihan, misal: 65000.00

            // Kolom untuk menandai periode tagihan, misal: '2025-07-01' untuk Juli 2025
            $table->date('period');

            $table->enum('status', ['pending', 'waiting_verification', 'paid'])->default('pending');
            $table->string('payment_proof_url')->nullable(); // Link ke foto bukti bayar
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
