<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\InvoiceGenerated;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\PaymentVerified;
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
        $dueDate = $period->copy()->day(10); // Menetapkan jatuh tempo tanggal 10

        // 1. Cek apakah tagihan untuk periode ini sudah pernah dibuat
        $existingInvoice = Invoice::where('period', $period)->first();
        if ($existingInvoice) {
            return response()->json(['message' => 'Tagihan untuk periode ini sudah pernah dibuat sebelumnya.'], 409); // 409 Conflict
        }

        // 2. Ambil semua user dengan peran 'warga'
        $warga = User::where('role', 'warga')->get();

        // 3. Gunakan Database Transaction
        // Jika ada satu saja yang gagal, semua data akan dibatalkan (rollback)
        DB::transaction(function () use ($warga, $period, $amount, $dueDate) {
            foreach ($warga as $user) {
                $invoice=Invoice::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'period' => $period,
                    'due_date' => $dueDate, 
                    'status' => 'pending', // Status awal tagihan
                ]);
                InvoiceGenerated::dispatch($invoice);
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

          // --- KIRIM NOTIFIKASI KE USER YANG BERSANGKUTAN ---
          $invoice->user->notify(new PaymentVerified($invoice));
          // ---------------------------------------------------

        return response()->json(['message' => 'Pembayaran berhasil diverifikasi.', 'invoice' => $invoice]);
    }

    /**
     * Menampilkan semua tagihan dari semua warga untuk dilihat admin.
     * Bisa difilter berdasarkan status.
     */
    public function getAllInvoices(Request $request)
    {
        $query = Invoice::with('user')->latest();

        // Tambahkan filter berdasarkan status jika ada
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(20);

        return response()->json($invoices);
    }

    /**
     * Memberikan rekapitulasi keuangan untuk periode tertentu.
     */
    public function getFinancialSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|numeric|digits:4',
            'month' => 'required|numeric|between:1,12',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $year = $request->year;
        $month = $request->month;

        // 1. Hitung Total Pemasukan
        // Diambil dari invoice yang statusnya 'paid' pada periode yang diminta
        $totalIncome = Invoice::where('status', 'paid')
            ->whereYear('updated_at', $year) // Asumsi 'updated_at' adalah tanggal verifikasi
            ->whereMonth('updated_at', $month)
            ->sum('amount');

        // 2. Hitung Total Pengeluaran
        $totalExpense = Expense::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');

        // 3. Hitung Saldo
        $balance = $totalIncome - $totalExpense;

        return response()->json([
            'period' => [
                'year' => $year,
                'month' => $month,
            ],
            'summary' => [
                'total_income' => (float) $totalIncome,
                'total_expense' => (float) $totalExpense,
                'balance' => $balance,
            ]
        ]);
    }
}
