@extends('layouts.app')

@section('header', 'Sửa thông tin sinh viên')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h5>Sửa thông tin sinh viên: {{ $student->mssv }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('students.update', $student->mssv) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label for="mssv" class="form-label">Mã số sinh viên</label>
                    <input type="text" class="form-control" id="mssv" value="{{ $student->mssv }}" readonly>
                    <small class="text-muted">MSSV không thể thay đổi</small>
                </div>

                <div class="mb-3">
                    <label for="hoten" class="form-label">Họ và tên</label>
                    <input type="text" class="form-control" id="hoten" name="hoten" value="{{ $student->hoten }}" required>
                </div>

                <div class="mb-3">
                    <label for="lop" class="form-label">Lớp</label>
                    <input type="text" class="form-control" id="lop" name="lop" value="{{ $student->lop }}" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ $student->email }}">
                </div>

                <div class="mb-3">
                    <label for="sdt" class="form-label">Số điện thoại</label>
                    <input type="text" class="form-control" id="sdt" name="sdt" value="{{ $student->sdt }}">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                    <a href="{{ route('students.edit.list') }}" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection