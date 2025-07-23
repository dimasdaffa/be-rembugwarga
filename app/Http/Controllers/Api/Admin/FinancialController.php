<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FinancialController extends Controller
{
    /**
     * Menampilkan daftar semua pengeluaran.
     */
    public function index()
    {
        // Mengambil data pengeluaran, diurutkan dari yang terbaru, dibagi per 15
        $expenses = Expense::latest()->paginate(15);
        return response()->json($expenses);
    }

    /**
     * Menyimpan data pengeluaran baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $expense = Expense::create($validator->validated());

        return response()->json(['message' => 'Pengeluaran berhasil dicatat', 'expense' => $expense], 201);
    }

    /**
     * Mengupdate data pengeluaran.
     */
    public function update(Request $request, Expense $expense)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $expense->update($validator->validated());

        return response()->json(['message' => 'Pengeluaran berhasil diupdate', 'expense' => $expense]);
    }

    /**
     * Menghapus data pengeluaran.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->noContent();
    }

    // =================================================================
    // == BAGIAN IURAN (INVOICES)
    // =================================================================

    /**
     * Membuat tagihan bulanan untuk semua warga sekaligus.
     */
    public function generateMonthlyInvoices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Inputnya adalah tanggal, misal "2025-08-01" untuk periode Agustus 2025
            'period' => 'required|date_format:Y-m-d',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $period = Carbon::parse($request->period)->startOfMonth();
        $amount = $request->amount;

        // 1. Cek apakah tagihan untuk periode ini sudah pernah dibuat
        $existingInvoice = Invoice::where('period', $period)->first();
        if ($existingInvoice) {
            return response()->json(['message' => 'Tagihan untuk periode ini sudah pernah dibuat sebelumnya.'], 409); // 409 Conflict
        }

        // 2. Ambil semua user dengan peran 'warga'
        $warga = User::where('role', 'warga')->get();

        // 3. Gunakan Database Transaction
        // Jika ada satu saja yang gagal, semua data akan dibatalkan (rollback)
        DB::transaction(function () use ($warga, $period, $amount) {
            foreach ($warga as $user) {
                Invoice::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'period' => $period,
                    'status' => 'pending', // Status awal tagihan
                ]);
            }
        });

        return response()->json(['message' => 'Berhasil membuat ' . $warga->count() . ' tagihan untuk periode ' . $period->format('F Y')], 201);
    }

    /**
     * Memverifikasi pembayaran dan mengubah status invoice menjadi 'paid'.
     */
    public function verifyPayment(Invoice $invoice)
    {
        // Cek apakah statusnya memang sedang menunggu verifikasi
        if ($invoice->status !== 'waiting_verification') {
            return response()->json(['message' => 'Tagihan ini tidak dalam status menunggu verifikasi.'], 409); // 409 Conflict
        }

        $invoice->update(['status' => 'paid']);

        return response()->json(['message' => 'Pembayaran berhasil diverifikasi.', 'invoice' => $invoice]);
    }

    /**
     * Menampilkan semua tagihan dari semua warga untuk dilihat admin.
     */
    public function getAllInvoices()
    {
        // Mengambil semua invoice, beserta data user-nya, diurutkan dari yang terbaru
        $invoices = Invoice::with('user')->latest()->paginate(20);

        return response()->json($invoices);
    }
}
