<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     */
    public function uploadProof(Request $request, Invoice $invoice)
    {
        // 1. Otorisasi: Pastikan user hanya bisa upload untuk tagihannya sendiri
        if ($request->user()->id !== $invoice->user_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // 2. Validasi: Pastikan ada file yang diupload dan itu adalah gambar
        $validator = Validator::make($request->all(), [
            'proof' => 'required|image|mimes:jpeg,png,jpg|max:2048', // maks 2MB
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 3. Simpan file
        $filePath = $request->file('proof')->store('payment_proofs', 'public');

        // 4. Update database
        $invoice->update([
            'payment_proof_url' => $filePath,
            'status' => 'waiting_verification',
        ]);

        return response()->json([
            'message' => 'Bukti pembayaran berhasil diupload.',
            'invoice' => $invoice
        ]);
    }
}
