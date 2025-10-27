@extends('layouts.app')

@section('header', 'Nhập sinh viên từ Excel')
@section('content')
<div class="card shadow-sm p-4">
    <h5 class="mb-3">Tải lên file Excel chứa danh sách sinh viên</h5>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="file" class="form-label">Chọn file Excel (.xlsx, .xls)</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
        </div>
        <button type="submit" class="btn btn-primary">Nhập dữ liệu</button>
    </form>
</div>
@endsection
