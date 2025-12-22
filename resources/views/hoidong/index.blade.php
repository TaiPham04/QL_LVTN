@extends('layouts.app')

@section('header', 'Quản Lý Hội Đồng')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fa fa-users me-2"></i> Danh Sách Hội Đồng
                    </h4>
                    <a href="{{ route('admin.hoidong.create') }}" class="btn btn-light btn-sm">
                        <i class="fa fa-plus me-1"></i> Tạo Hội Đồng Mới
                    </a>
                </div>

                <div class="card-body">
                    {{-- Alerts --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Danh sách --}}
                    @if($danhSachHoiDong->isEmpty())
                        <div class="alert alert-warning text-center">
                            <i class="fa fa-exclamation-triangle fa-3x mb-3"></i>
                            <h5>Chưa có hội đồng nào</h5>
                            <p class="mb-0">Nhấn nút "Tạo Hội Đồng Mới" để bắt đầu.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%" class="text-center">STT</th>
                                        <th width="15%">Mã Hội Đồng</th>
                                        <th width="30%">Tên Hội Đồng</th>
                                        <th width="10%" class="text-center">Thành Viên</th>
                                        <th width="10%" class="text-center">Đề Tài</th>
                                        <th width="10%" class="text-center">Trạng Thái</th>
                                        <th width="20%" class="text-center">Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($danhSachHoiDong as $index => $hd)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>
                                            <strong class="text-primary">{{ $hd->mahd }}</strong>
                                        </td>
                                        <td>{{ $hd->tenhd }}</td>
                                        <td class="text-center">
                                            @if($hd->so_thanh_vien == 3)
                                                <span class="badge bg-success">
                                                    <i class="fa fa-check me-1"></i>{{ $hd->so_thanh_vien }}/3
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fa fa-exclamation me-1"></i>{{ $hd->so_thanh_vien }}/3
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $hd->so_de_tai }} đề tài</span>
                                        </td>
                                        <td class="text-center">
                                            @if($hd->trang_thai == 'dang_mo')
                                                <span class="badge bg-success">Đang mở</span>
                                            @else
                                                <span class="badge bg-secondary">Đã đóng</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.hoidong.show', $hd->id) }}" 
                                               class="btn btn-sm btn-info" title="Chi tiết">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.hoidong.phancong.form', $hd->id) }}" 
                                               class="btn btn-sm btn-primary" title="Phân công">
                                                <i class="fa fa-tasks"></i>
                                            </a>

                                            @if($hd->so_de_tai > 0)
                                                <a href="{{ route('admin.hoidong.export.excel', $hd->id) }}" 
                                                class="btn btn-sm btn-success" 
                                                title="Xuất Excel">
                                                    <i class="fa fa-file-excel"></i>
                                                </a>
                                            @endif
                                            
                                            {{-- KIỂM TRA: Chỉ hiện nút xóa nếu CHƯA có đề tài --}}
                                            @if($hd->so_de_tai == 0)
                                                <form action="{{ route('admin.hoidong.destroy', $hd->id) }}" 
                                                    method="POST" class="d-inline" 
                                                    onsubmit="return confirm('Xác nhận xóa hội đồng này?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Xóa">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Hiện tooltip giải thích tại sao không xóa được --}}
                                                <button type="button" 
                                                        class="btn btn-sm btn-secondary" 
                                                        title="Không thể xóa vì đã có {{ $hd->so_de_tai }} đề tài"
                                                        disabled>
                                                    <i class="fa fa-lock"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection