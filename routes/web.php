<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    AdminController,
    LecturerController,
    StudentController,
    PhanBienController,
    AdminAssignmentController,
    LecturerAssignmentsController
};

/*
|--------------------------------------------------------------------------
| TRANG CHỦ VÀ ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/

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
        
        Route::get('/topics', [AdminController::class, 'topics'])->name('topics.index');
        
        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::get('/', [AdminAssignmentController::class, 'index'])->name('index');
            Route::get('/form', [AdminAssignmentController::class, 'form'])->name('form');
            Route::post('/store', [AdminAssignmentController::class, 'store'])->name('store');
            Route::delete('/{mssv}', [AdminAssignmentController::class, 'destroy'])->name('destroy');
        });
        
        Route::get('/phanbien', [PhanBienController::class, 'index'])->name('phanbien.index');
        Route::post('/phanbien/store', [PhanBienController::class, 'store'])->name('phanbien.store');

        Route::prefix('hoidong')->name('hoidong.')->group(function () {
            Route::get('/', [App\Http\Controllers\HoiDongController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\HoiDongController::class, 'create'])->name('create');
            Route::post('/store', [App\Http\Controllers\HoiDongController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\HoiDongController::class, 'show'])->name('show');
            Route::delete('/{id}', [App\Http\Controllers\HoiDongController::class, 'destroy'])->name('destroy');
            
            Route::get('/{id}/phan-cong', [App\Http\Controllers\HoiDongController::class, 'phanCongForm'])->name('phancong.form');
            Route::post('/{id}/phan-cong', [App\Http\Controllers\HoiDongController::class, 'phanCongStore'])->name('phancong.store');
            Route::delete('/{id}/phan-cong/{nhom_id}', [App\Http\Controllers\HoiDongController::class, 'phanCongDelete'])->name('phancong.delete');
            Route::get('/{id}/export-excel', [App\Http\Controllers\HoiDongController::class, 'exportExcel'])->name('export.excel');
        });
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

        Route::get('/students', [StudentController::class, 'index'])->name('students.index');

        // Phân công đề tài
        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::get('/', [LecturerAssignmentsController::class, 'index'])->name('index');
            Route::get('/form', [LecturerAssignmentsController::class, 'form'])->name('form');
            Route::post('/store', [LecturerAssignmentsController::class, 'store'])->name('store');
            Route::get('/{nhom_id}', [LecturerAssignmentsController::class, 'show'])->name('show');
            Route::get('/{nhom_id}/edit', [LecturerAssignmentsController::class, 'edit'])->name('edit');
            Route::put('/{nhom_id}', [LecturerAssignmentsController::class, 'update'])->name('update');
            Route::delete('/{nhom_id}', [LecturerAssignmentsController::class, 'destroy'])->name('destroy');
            Route::post('/update-all-status', [LecturerAssignmentsController::class, 'updateAllStatus'])->name('update-all-status');
            Route::post('/{nhom_id}/update-status', [LecturerAssignmentsController::class, 'updateStatus'])->name('updateStatus');
        });

        // Chấm điểm hướng dẫn
        Route::prefix('cham-diem-huong-dan')->name('chamdiem.huongdan.')->group(function () {
            Route::get('/', [App\Http\Controllers\ChamDiemController::class, 'indexHuongDan'])->name('index');
            Route::get('/{nhom_id}', [App\Http\Controllers\ChamDiemController::class, 'formHuongDan'])->name('form');
            Route::post('/{nhom_id}', [App\Http\Controllers\ChamDiemController::class, 'storeHuongDan'])->name('store');
            Route::get('/{nhom_id}/export', [App\Http\Controllers\ChamDiemController::class, 'exportHuongDan'])->name('export');
        });

        // Chấm điểm phản biện
        Route::prefix('cham-diem-phan-bien')->name('chamdiem.phanbien.')->group(function () {
            Route::get('/', [App\Http\Controllers\ChamDiemController::class, 'indexPhanBien'])->name('index');
            Route::get('/{nhom_id}/form', [App\Http\Controllers\ChamDiemController::class, 'formPhanBien'])->name('form')->where('nhom_id', '[0-9]+');
            Route::post('/{nhom_id}', [App\Http\Controllers\ChamDiemController::class, 'storePhanBien'])->name('store')->where('nhom_id', '[0-9]+');
            Route::get('/{nhom_id}/export', [App\Http\Controllers\ChamDiemController::class, 'exportPhanBien'])->name('export')->where('nhom_id', '[0-9]+');
        });

        // Điểm giữa kỳ
        Route::get('/diem-giuaky', [App\Http\Controllers\DiemGiuaKyController::class, 'index'])->name('diemgiuaky.index');
        Route::post('/diem-giuaky/store', [App\Http\Controllers\DiemGiuaKyController::class, 'store'])->name('diemgiuaky.store');
        Route::get('/diem-giuaky/export', [App\Http\Controllers\DiemGiuaKyController::class, 'export'])->name('diemgiuaky.export');
        
        // Nhiệm vụ
        Route::prefix('nhiemvu')->name('nhiemvu.')->group(function () {
            Route::get('/', [App\Http\Controllers\NhiemVuController::class, 'index'])->name('index');
            Route::get('/{nhom_id}/create', [App\Http\Controllers\NhiemVuController::class, 'create'])->name('create');
            Route::post('/store', [App\Http\Controllers\NhiemVuController::class, 'store'])->name('store');
            Route::get('/{nhom_id}/export', [App\Http\Controllers\NhiemVuController::class, 'exportWord'])->name('export');
        });

        // ✅ CHẤM ĐIỂM HỘI ĐỒNG
        Route::prefix('cham-diem/hoi-dong')->name('cham-diem.hoi-dong.')->controller(\App\Http\Controllers\ChamDiemHoiDongController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{mahd}/form', 'form')->name('form');
            Route::post('/{mahd}', 'store')->name('store');
            Route::get('/{mahd}/export-excel', 'exportExcel')->name('export-excel');
        });

        // ✅ QUẢN LÝ GIẢNG VIÊN (Giảng viên tự quản lý profile)
        Route::prefix('profile')->name('profile.')->controller(LecturerController::class)->group(function () {
            Route::get('/edit/{magv}', 'edit')->name('edit');
            Route::post('/update/{magv}', 'update')->name('update');
        });

        // Route Điểm Tổng Kết
        Route::prefix('tong-ket')->name('tong-ket.')->controller(\App\Http\Controllers\TongKetController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{mahd}/export-excel', 'exportExcel')->name('export-excel');
        });
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

Route::get('/students/export', [StudentController::class, 'export'])->name('students.export')->middleware('auth');



/*
|--------------------------------------------------------------------------
| ROUTE QUẢN LÝ GIẢNG VIÊN (CHO ADMIN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->controller(LecturerController::class)
    ->prefix('admin/lecturers')
    ->name('admin.lecturers.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit-list', 'editList')->name('edit.list');
        Route::get('/edit/{magv}', 'edit')->name('edit');
        Route::post('/update/{magv}', 'update')->name('update');
        Route::get('/delete/{id}', 'destroy')->name('delete');
        Route::post('/import', 'import')->name('import');
    });

// Alias routes để View dùng lecturers.* được
Route::middleware(['auth', 'admin'])
    ->controller(LecturerController::class)
    ->name('lecturers.')
    ->group(function () {
        Route::get('/lecturers/create', 'create')->name('create');
        Route::post('/lecturers/store', 'store')->name('store');
        Route::get('/lecturers/edit-list', 'editList')->name('edit.list');
        Route::get('/lecturers/edit/{magv}', 'edit')->name('edit');
        Route::post('/lecturers/update/{magv}', 'update')->name('update');
        Route::post('/lecturers/import', 'import')->name('import');
    });

Route::post('/lecturers/import', [LecturerController::class, 'import'])
    ->middleware(['auth', 'admin'])
    ->name('lecturersManagement.import');