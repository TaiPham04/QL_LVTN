@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">
                <i class="fa fa-clipboard-list me-2 text-primary"></i>Quản Lý Nhiệm Vụ Đồ Án
            </h3>
            <p class="text-muted mb-0">Nhập thông tin nhiệm vụ cho sinh viên</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- HƯỚNG DẪN --}}
    <div class="alert alert-info mb-4">
        <i class="fa fa-info-circle me-2"></i>
        <strong>Hướng dẫn:</strong> Dưới đây là danh sách các nhóm sinh viên bạn đang hướng dẫn. Nhấn vào nút "Chấm điểm" để thực hiện chấm điểm cho nhóm.
    </div>

    {{-- TABLE --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%">STT</th>
                            <th style="width: 12%">Mã Nhóm</th>
                            <th style="width: 35%">Tên Đề Tài</th>
                            <th style="width: 10%" class="text-center">Số SV</th>
                            <th style="width: 15%" class="text-center">Trạng Thái</th>
                            <th style="width: 20%" class="text-center">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $index => $group)
                            <tr>
                                <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge bg-primary px-3 py-2">{{ $group->nhom }}</span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 400px;" title="{{ $group->tenduan }}">
                                        {{ $group->tenduan }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info text-dark">{{ $group->so_sv }} sinh viên</span>
                                </td>
                                <td class="text-center">
                                    @if($group->trangthai == 'Đã điền')
                                        <span class="badge bg-success px-3 py-2">
                                            <i class="fa fa-check-circle me-1"></i>Đã điền
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark px-3 py-2">
                                            <i class="fa fa-clock me-1"></i>Chưa điền
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('lecturers.nhiemvu.create', $group->nhom) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fa fa-edit me-1"></i>Điền thông tin
                                        </a>
                                        
                                        @if($group->trangthai == 'Đã điền')
                                            <a href="{{ route('lecturers.nhiemvu.export', $group->nhom) }}" 
                                               class="btn btn-sm btn-success">
                                                <i class="fa fa-file-word me-1"></i>Xuất Word
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">Chưa có nhóm nào được phân công</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.table td {
    font-size: 14px;
    vertical-align: middle;
}

.badge {
    font-weight: 500;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}
</style>
@endsection