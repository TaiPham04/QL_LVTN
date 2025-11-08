<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            session(['user' => $user]);

            // Điều hướng theo role
            switch ($user->role) {
                case 'admin':
                    return redirect()->route('admin.dashboard');
                case 'giangvien':
                    return redirect()->route('lecturers.home');
                default:
                    session()->forget('user');
                    return redirect()->route('login')->with('error', 'Không xác định được quyền truy cập');
            }
        }

        return back()->with('error', 'Tên đăng nhập hoặc mật khẩu không đúng');
    }

    public function logout(Request $request)
    {
        session()->forget('user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
