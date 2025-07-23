<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        // Ambil semua user, bisa ditambahkan pagination jika datanya banyak
        $users = User::paginate(15);

        return response()->json($users);
    }
    /**
     * Mengupdate data user oleh admin.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:users,phone_number,' . $user->id,
            'house_number' => 'required|string',
            'role' => 'required|in:warga,pengurus',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->update($validator->validated());

        return response()->json(['message' => 'User berhasil diupdate', 'user' => $user]);
    }

    /**
     * Menghapus user oleh admin.
     */
    public function destroy(User $user)
    {
        // Mungkin Anda tidak ingin user bisa menghapus dirinya sendiri
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Anda tidak bisa menghapus akun sendiri.'], 403);
        }

        $user->delete();

        return response()->noContent();
    }
}
