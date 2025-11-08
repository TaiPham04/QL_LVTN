@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-primary">Danh sách giảng viên</h3>
    </div>

    {{-- Hiển thị thông báo nếu có --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã GV</th>
                        <th>Họ và tên</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lecturers as $gv)
                        <tr>
                            <td>{{ $gv->magv }}</td>
                            <td>{{ $gv->hoten }}</td>
                            <td>{{ $gv->email }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">Chưa có giảng viên nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
