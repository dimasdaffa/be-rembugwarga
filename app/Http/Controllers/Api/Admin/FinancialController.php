<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
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
}
