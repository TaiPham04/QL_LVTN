<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    AdminController,
    LecturerController,
    LecturerAssignmentController,
    StudentController,
    AssignmentController
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
    });



/*
|--------------------------------------------------------------------------
| ROUTE DÀNH CHO GIẢNG VIÊN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'lecturers']) // 👈 middleware LecturersMiddleware
    ->prefix('lecturers')                // 👈 thư mục view của bạn là “lecturers” có chữ s
    ->name('lecturers.')
    ->group(function () {

        // Trang chủ
        Route::get('/home', function () {
            return view('lecturers.home');
        })->name('home');

        // Danh sách sinh viên
        Route::get('/students', [StudentController::class, 'index'])
            ->name('students.index');

        // Nhóm & Đề tài
        Route::get('/assignments/form', [LecturerAssignmentController::class, 'index'])
            ->name('assignments.form');

        // Lưu phân công
        Route::post('/assignments/store', [LecturerAssignmentController::class, 'store'])
            ->name('assignments.store');
    });



/*
|--------------------------------------------------------------------------
| ROUTE DÀNH CHO SINH VIÊN
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
    ->prefix('lecturers-management')
    ->name('lecturersManagement.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit-list', 'editList')->name('edit.list');
        Route::get('/edit/{magv}', 'edit')->name('edit');
        Route::post('/update/{magv}', 'update')->name('update');
        Route::post('/import', 'import')->name('import');
    });



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
| TRANG KHÁC / TEST
|--------------------------------------------------------------------------
*/
Route::get('/timeline', fn() => view('dashboard'))->name('timeline.index');
Route::get('/settings', fn() => view('dashboard'))->name('settings.index');
Route::get('/layout/index', fn() => view('layouts.app'))->name('layouts.app');


Route::prefix('lecturers')->group(function () {
    Route::get('/', [LecturerController::class, 'index'])->name('lecturers.index');
    Route::get('/create', [LecturerController::class, 'create'])->name('lecturers.create');
    Route::post('/store', [LecturerController::class, 'store'])->name('lecturers.store');
    Route::get('/edit/{id}', [LecturerController::class, 'edit'])->name('lecturers.edit');
    Route::post('/update/{id}', [LecturerController::class, 'update'])->name('lecturers.update');
    Route::get('/delete/{id}', [LecturerController::class, 'destroy'])->name('lecturers.delete');

    // Trang home riêng của giảng viên
    Route::get('/home', [LecturerController::class, 'home'])->name('lecturers.home');

    // Route để giảng viên xem danh sách sinh viên
    Route::get('/students', [LecturerController::class, 'students'])->name('lecturers.students');
});

Route::get('/edit-list', [LecturerController::class, 'editList'])->name('lecturers.edit.list');

Route::get('/lecturers/home', function () {
    return view('lecturers.home');
})->name('lecturers.home');

Route::middleware(['auth', 'lecturer'])
    ->prefix('lecturers')
    ->name('lecturers.')
    ->group(function () {

        // Trang chủ giảng viên
        Route::get('/home', fn() => view('lecturers.home'))->name('home');

        // Danh sách sinh viên
        Route::get('/students', [App\Http\Controllers\StudentController::class, 'index'])
            ->name('students.index');

        // Nhóm & đề tài
        Route::get('/assignments/form', [App\Http\Controllers\LecturerAssignmentController::class, 'index'])
            ->name('assignments.form');

        // Lưu phân công
        Route::post('/assignments/store', [App\Http\Controllers\LecturerAssignmentController::class, 'store'])
            ->name('assignments.store');
    });