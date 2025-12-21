@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Bảng phân công giảng viên</h4>
        </div>
        <a href="{{ route('admin.assignments.form') }}" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Phân công mới
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.assignments.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Tìm theo MSSV, tên sinh viên..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="magv" class="form-select">
                            <option value="">-- Tất cả giảng viên --</option>
                            @if(isset($lecturers))
                                @foreach($lecturers as $lecturer)
                                    <option value="{{ $lecturer->magv }}" {{ request('magv') == $lecturer->magv ? 'selected' : '' }}>
                                        {{ $lecturer->hoten }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Đã phân công</option>
                            <option value="unassigned" {{ request('status') == 'unassigned' ? 'selected' : '' }}>Chưa phân công</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Tìm
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignments Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if(isset($assignments) && count($assignments) > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">STT</th>
                                <th style="width: 12%">MSSV</th>
                                <th style="width: 20%">Họ tên sinh viên</th>
                                <th style="width: 10%">Lớp</th>
                                <th style="width: 25%">Giảng viên hướng dẫn</th>
                                <th style="width: 12%">Có Đề Tài</th>
                                <th style="width: 8%" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignments as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $item->mssv }}</strong></td>
                                    <td>{{ $item->hoten }}</td>
                                    <td>{{ $item->lop ?? 'N/A' }}</td>
                                    <td>
                                        @if($item->magv)
                                            {{ $item->tengiangvien ?? 'N/A' }}
                                            <span class="badge bg-success ms-2">Đã phân công</span>
                                        @else
                                            <span class="badge bg-secondary">Chưa phân công</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item->co_de_tai)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Có
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-times-circle me-1"></i>Chưa
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item->magv)
                                            @if($item->co_de_tai)
                                                <!-- Sinh viên đã có đề tài → DISABLED -->
                                                <button class="btn btn-sm btn-outline-danger disabled" 
                                                        disabled 
                                                        title="Không thể hủy: Sinh viên đã có đề tài">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @else
                                                <!-- Sinh viên chưa có đề tài → CÓ THỂ HỦY -->
                                                <form action="{{ route('admin.assignments.destroy', $item->mssv) }}" 
                                                      method="POST" 
                                                      style="display: inline;"
                                                      onsubmit="return confirm('Bạn có chắc muốn hủy phân công?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hủy phân công">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(method_exists($assignments, 'links'))
                    <div class="mt-3">
                        {{ $assignments->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted mb-2">Chưa có phân công nào</h5>
                    <p class="text-muted small mb-3">Bắt đầu phân công giảng viên cho sinh viên</p>
                    <a href="{{ route('admin.assignments.form') }}" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Phân công ngay
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 12px;
}

.form-control, .form-select {
    border-radius: 8px;
    padding: 10px 14px;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
}

.table th {
    font-weight: 600;
    color: #495057;
}

.badge {
    padding: 6px 12px;
    font-weight: 500;
}

.btn.disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-outline-danger.disabled:hover {
    background-color: transparent;
    border-color: #dc3545;
    color: #dc3545;
}
</style>

@endsection