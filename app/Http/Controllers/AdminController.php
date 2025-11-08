<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        // Trả về view trang quản lý admin
        return view('layouts.app');
    }
}
