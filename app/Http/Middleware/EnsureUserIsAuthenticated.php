<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini');
        }

        if (auth()->user()->role !== 'user') {
            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini');
        }

        return $next($request);
    }
}
