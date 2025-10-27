@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-primary">Thêm sinh viên</h3>

        <div class="d-flex justify-content-end mb-3">
            <!-- Thay đổi nút "Thêm bằng file" để mở dialog -->
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                Thêm bằng file
            </button>
        </div>
    </div>

    <div class="card shadow-sm p-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('students.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label">Mã số sinh viên</label>
                <input type="text" name="mssv" class="form-control" placeholder="Nhập mã số sinh viên" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Họ và tên</label>
                <input type="text" name="hoten" class="form-control" placeholder="Nhập họ tên sinh viên" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Lớp</label>
                <input type="text" name="lop" class="form-control" placeholder="Nhập lớp" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="Nhập email" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Số điện thoại</label>
                <input type="text" name="sdt" class="form-control" placeholder="Nhập số điện thoại" required>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Lưu sinh viên</button>
            </div>
        </form>
    </div>
</div>

<!-- Thêm dialog/modal cho import file -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Thêm sinh viên bằng file Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label">Chọn file Excel</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls" required>
                        <div class="form-text">Chỉ chấp nhận file .xlsx hoặc .xls</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" form="importForm" class="btn btn-success">Import</button>
            </div>
        </div>
    </div>
</div>
@endsection