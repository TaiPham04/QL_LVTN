<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LecturersMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = session('user');
        if (!$user || $user->role !== 'giangvien') {
            return redirect()->route('login')->with('error', 'Bạn không có quyền truy cập');
        }
        return $next($request);
    }
    
}
