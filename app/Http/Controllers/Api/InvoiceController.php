<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Menampilkan daftar tagihan milik user yang sedang login.
     */
    public function index(Request $request)
    {
        // Ambil data user yang sedang login
        $user = $request->user();

        // Ambil semua tagihan milik user tersebut, urutkan berdasarkan periode
        $invoices = $user->invoices()->orderBy('period', 'desc')->paginate(12);

        return response()->json($invoices);
    }

    /**
     * Mengupload bukti pembayaran untuk sebuah tagihan.
     * Akan kita isi nanti.
     */
    public function uploadProof(Request $request, Invoice $invoice)
    {
        // Logika untuk upload bukti bayar akan kita tambahkan di sini nanti
    }
}
