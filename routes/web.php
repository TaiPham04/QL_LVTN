<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;

Route::get('/', fn() => view('dashboard'))->name('dashboard');
Route::get('/timeline', fn() => view('dashboard'))->name('timeline.index');

Route::prefix('students')->group(function () {
    Route::get('/create', fn() => view('dashboard'))->name('students.create');
    Route::get('/edit', fn() => view('dashboard'))->name('students.edit');
});

Route::get('/lecturers', fn() => view('dashboard'))->name('lecturers.index');
Route::get('/assignments', fn() => view('dashboard'))->name('assignments.index');
Route::get('/settings', fn() => view('dashboard'))->name('settings.index');

Route::controller(StudentController::class)->group(function(){
    
    
    Route::get('/students/create','create')->name('students.create');
    Route::post('/students','store')->name('students.store');
    Route::get('/students','index')->name('students.index');

    
    Route::get('/students/import', 'showImportForm')->name('students.import.form');
    Route::post('/students/import', 'import')->name('students.import');

    
    Route::get('/students/{mssv}/edit','edit')->name('students.edit');
    Route::put('/students/{mssv}','update')->name('students.update');

    Route::get('/students/edit-list','showEditList')->name('students.edit.list');
    Route::get('/students/{mssv}/edit-form','edit')->name('students.edit.form');
});

// ----Dữ Liệu Gốc----
// Route cho Student - SẮP XẾP LẠI VÀ XÓA TRÙNG LẶP
// Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
// Route::post('/students', [StudentController::class, 'store'])->name('students.store');
// Route::get('/students', [StudentController::class, 'index'])->name('students.index');

// Thêm sinh viên bằng file
// Route::get('/students/import', [StudentController::class, 'showImportForm'])->name('students.import.form');
// Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');

// Sửa sinh viên
// Route::get('/students/{mssv}/edit', [StudentController::class, 'edit'])->name('students.edit');
// Route::put('/students/{mssv}', [StudentController::class, 'update'])->name('students.update');

// Route::get('/students/edit-list', [StudentController::class, 'showEditList'])->name('students.edit.list');
// Route::get('/students/{mssv}/edit-form', [StudentController::class, 'edit'])->name('students.edit.form');
