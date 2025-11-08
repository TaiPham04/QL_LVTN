<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Nếu chưa đăng nhập thì điều hướng về trang login
        if (!session()->has('user')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
