<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // Ambil notifikasi milik user yang login, urutkan dari yg terbaru
        $notifications = $request->user()->notifications()->paginate(15);
        return response()->json($notifications);
    }
}
