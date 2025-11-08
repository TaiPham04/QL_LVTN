<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $user = session('user');
        if (!$user || $user->role !== 'admin') {
            return redirect()->route('login')->with('error', 'Bạn không có quyền truy cập');
        }
        return $next($request);
    }

}
