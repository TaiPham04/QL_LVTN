@extends('layouts.app')

@section('header', 'Chỉnh sửa giảng viên')

@section('content')
<div class="container">
    <h5>Chỉnh sửa thông tin giảng viên</h5>

    <form action="{{ route('lecturers.update', $lecturer->magv) }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="hoten" class="form-label">Họ tên</label>
            <input type="text" name="hoten" id="hoten" class="form-control"
                   value="{{ old('hoten', $lecturer->hoten) }}" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control"
                   value="{{ old('email', $lecturer->email) }}" required>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-primary">Cập nhật</button>
            <a href="{{ route('lecturers.edit.list') }}" class="btn btn-secondary">Quay lại</a>
        </div>
    </form>
</div>
@endsection
