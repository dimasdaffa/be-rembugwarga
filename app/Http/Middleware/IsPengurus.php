<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsPengurus
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role === 'pengurus') {
            return $next($request);
        }

        return response()->json(['message' => 'Akses ditolak. Hanya untuk pengurus.'], 403);
    }
}
