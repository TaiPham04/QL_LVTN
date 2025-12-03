@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fa fa-user-check me-2"></i>
                        Chấm Điểm Phản Biện
                    </h4>
                </div>

                <div class="card-body">
                    {{-- Alert Messages --}}
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

                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fa fa-info-circle me-2"></i>{{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Hướng dẫn --}}
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>
                        <strong>Hướng dẫn:</strong> Dưới đây là danh sách các nhóm bạn được phân công phản biện. 
                        Nhấn vào nút "Chấm điểm" để thực hiện chấm điểm phản biện.
                    </div>

                    {{-- Danh sách nhóm --}}
                    @if($danhSachNhom->isEmpty())
                        <div class="alert alert-warning text-center">
                            <i class="fa fa-exclamation-triangle fa-3x mb-3"></i>
                            <h5>Chưa có nhóm nào cần chấm điểm phản biện</h5>
                            <p class="mb-0">Hiện tại bạn chưa được phân công phản biện nhóm nào.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-warning">
                                    <tr>
                                        <th width="5%" class="text-center">STT</th>
                                        <th width="10%" class="text-center">Mã Nhóm</th>
                                        <th width="40%">Tên Đề Tài</th>
                                        <th width="10%" class="text-center">Số SV</th>
                                        <th width="15%" class="text-center">Trạng Thái</th>
                                        <th width="20%" class="text-center">Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($danhSachNhom as $index => $nhom)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-center">
                                            <strong class="badge bg-secondary">{{ $nhom->nhom }}</strong>
                                        </td>
                                        <td>{{ $nhom->tendt }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $nhom->so_luong_sv }} sinh viên</span>
                                        </td>
                                        <td class="text-center">
                                            @if($nhom->da_cham)
                                                <span class="badge bg-success">
                                                    <i class="fa fa-check me-1"></i>Đã chấm
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fa fa-clock me-1"></i>Chưa chấm
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('lecturers.chamdiem.phanbien.form', $nhom->nhom) }}" 
                                               class="btn btn-sm {{ $nhom->da_cham ? 'btn-warning' : 'btn-primary' }}">
                                                <i class="fa {{ $nhom->da_cham ? 'fa-edit' : 'fa-pen' }} me-1"></i>
                                                {{ $nhom->da_cham ? 'Sửa điểm' : 'Chấm điểm' }}
                                            </a>

                                            @if($nhom->da_cham)
                                                <a href="{{ route('lecturers.chamdiem.phanbien.export', $nhom->nhom) }}" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fa fa-file-word me-1"></i>Xuất Word
                                                </a>
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