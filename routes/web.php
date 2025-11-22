<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    AdminController,
    LecturerController,
    LecturerAssignmentController,
    StudentController,
    AssignmentController,
    PhanBienController
};

/*
|--------------------------------------------------------------------------
| TRANG CHỦ VÀ ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/

// Trang chủ - tự động điều hướng theo vai trò
Route::get('/', function () {
    $user = session('user');
    if (!$user) {
        return redirect()->route('login');
    }

    switch ($user->role) {
        case 'admin':
            return redirect()->route('admin.dashboard');
        case 'giangvien':
            return redirect()->route('lecturers.home');
        default:
            return redirect()->route('login');
    }
});

// Đăng nhập / đăng xuất
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');



/*
|--------------------------------------------------------------------------
| ROUTE DÀNH CHO ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        
        // Danh sách đề tài
        Route::get('/topics', [AdminController::class, 'topics'])->name('topics.index');
        
        // Phân công phản biện
        Route::get('/phanbien', [PhanBienController::class, 'index'])->name('phanbien.index');
        Route::post('/phanbien/store', [PhanBienController::class, 'store'])->name('phanbien.store');
    });



/*
|--------------------------------------------------------------------------
| ROUTE DÀNH CHO GIẢNG VIÊN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'lecturers'])
    ->prefix('lecturers')
    ->name('lecturers.')
    ->group(function () {
        // Trang chủ
        Route::get('/home', function () {
            return view('lecturers.home');
        })->name('home');

        // Danh sách sinh viên
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');

        // Nhóm & Đề tài
        Route::get('/assignments/form', [LecturerAssignmentController::class, 'index'])->name('assignments.form');
        Route::post('/assignments/store', [LecturerAssignmentController::class, 'store'])->name('assignments.store');
        Route::post('/assignments/delete', [LecturerAssignmentController::class, 'deleteSelected'])->name('assignments.delete');
        
        // Gửi đề tài cho admin
        Route::post('/send-to-admin', [LecturerAssignmentController::class, 'sendToAdmin'])->name('sendToAdmin');
    });



/*
|--------------------------------------------------------------------------
| ROUTE QUẢN LÝ SINH VIÊN
|--------------------------------------------------------------------------
*/
Route::controller(StudentController::class)
    ->prefix('students')
    ->name('students.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/import', 'showImportForm')->name('import.form');
        Route::post('/import', 'import')->name('import');
        Route::get('/edit-list', 'showEditList')->name('edit.list');
        Route::get('/{mssv}/edit', 'edit')->name('edit');
        Route::put('/{mssv}', 'update')->name('update');
    });



/*
|--------------------------------------------------------------------------
| ROUTE QUẢN LÝ GIẢNG VIÊN (ADMIN)
|--------------------------------------------------------------------------
*/
Route::controller(LecturerController::class)
    ->prefix('lecturers')
    ->name('lecturers.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit-list', 'editList')->name('edit.list');
        Route::get('/edit/{magv}', 'edit')->name('edit');
        Route::post('/update/{magv}', 'update')->name('update');
        Route::get('/delete/{id}', 'destroy')->name('delete');
    });

// Route riêng cho import (để tương thích với view cũ)
Route::post('/lecturers/import', [LecturerController::class, 'import'])->name('lecturersManagement.import');



/*
|--------------------------------------------------------------------------
| ROUTE PHÂN CÔNG (ADMIN)
|--------------------------------------------------------------------------
*/
Route::controller(AssignmentController::class)
    ->prefix('assignments')
    ->name('assignments.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/form', 'showForm')->name('form');
        Route::post('/save', 'save')->name('save');
        Route::post('/bulk-save', 'bulkSave')->name('bulkSave');
    });



/*
|--------------------------------------------------------------------------
| ROUTE KHÁC
|--------------------------------------------------------------------------
*/
Route::get('/timeline', fn() => view('dashboard'))->name('timeline.index');
Route::get('/settings', fn() => view('dashboard'))->name('settings.index');