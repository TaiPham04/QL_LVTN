@extends('layouts.app')

@section('header', 'Chi Tiết Hội Đồng')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            {{-- Thông tin hội đồng --}}
            <div class="card mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fa fa-info-circle me-2"></i> Thông Tin Hội Đồng
                    </h4>
                    <div>
                        <a href="{{ route('admin.hoidong.phancong.form', $hoiDong->id) }}" 
                           class="btn btn-light btn-sm me-2">
                            <i class="fa fa-tasks me-1"></i> Phân Công Đề Tài
                        </a>
                        <a href="{{ route('admin.hoidong.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left me-1"></i> Quay Lại
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Mã hội đồng:</strong> <span class="badge bg-primary">{{ $hoiDong->mahd }}</span></p>
                            <p><strong>Tên hội đồng:</strong> {{ $hoiDong->tenhd }}</p>
                            <p>
                                <strong>Ngày hội đồng:</strong> 
                                @if($hoiDong->ngay_hoidong)
                                    <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 0.6rem 1rem;">
                                        <i class="fa fa-calendar me-2"></i>
                                        {{ \Carbon\Carbon::parse($hoiDong->ngay_hoidong)->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">Chưa chọn</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Trạng thái:</strong> 
                                @if($hoiDong->trang_thai == 'dang_mo')
                                    <span class="badge bg-success">Đang mở</span>
                                @else
                                    <span class="badge bg-secondary">Đã đóng</span>
                                @endif
                            </p>
                            <p><strong>Ghi chú:</strong> {{ $hoiDong->ghi_chu ?? 'Không có' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Thành viên hội đồng --}}
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-users me-2"></i> Thành Viên Hội Đồng ({{ $thanhVien->count() }}/3-4)
                    </h5>
                </div>
                <div class="card-body">
                    @if($thanhVien->isEmpty())
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Chưa có thành viên nào trong hội đồng!
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%" class="text-center">STT</th>
                                        <th width="12%">Mã GV</th>
                                        <th width="33%">Họ Tên</th>
                                        <th width="33%">Email</th>
                                        <th width="17%" class="text-center">Vai Trò</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($thanhVien as $index => $tv)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td><strong>{{ $tv->magv }}</strong></td>
                                        <td>{{ $tv->hoten }}</td>
                                        <td>{{ $tv->email }}</td>
                                        <td class="text-center">
                                            @if($tv->vai_tro == 'chu_tich')
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fa fa-crown me-1"></i>Chủ tịch
                                                </span>
                                            @elseif($tv->vai_tro == 'thu_ky')
                                                <span class="badge bg-info text-white">
                                                    <i class="fa fa-file-alt me-1"></i>Thư ký
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fa fa-user me-1"></i>Thành viên
                                                </span>
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

            {{-- Đề tài đã phân công --}}
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-clipboard-list me-2"></i> Đề Tài Đã Phân Công ({{ $deTai->count() }})
                    </h5>
                </div>
                <div class="card-body">
                    @if($deTai->isEmpty())
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Chưa có đề tài nào được phân công!
                            <a href="{{ route('admin.hoidong.phancong.form', $hoiDong->id) }}" 
                               class="btn btn-sm btn-primary ms-3">
                                <i class="fa fa-plus me-1"></i> Phân Công Ngay
                            </a>
                        </div>
                    @else
                        @foreach($deTai as $index => $dt)
                        <div class="card mb-3 border-primary">
                            <div class="card-header bg-primary bg-opacity-10">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fa fa-folder me-2"></i>
                                        {{ $index + 1 }}. Nhóm: <strong>{{ $dt->nhom }}</strong>
                                    </h6>
                                    <form action="{{ route('admin.hoidong.phancong.delete', [$hoiDong->id, $dt->nhom_id]) }}" 
                                          method="POST" 
                                          onsubmit="return confirm('Xác nhận xóa đề tài này khỏi hội đồng?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fa fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Tên đề tài:</strong> {{ $dt->tendt }}</p>
                                <p class="mb-2">
                                    <strong>GV hướng dẫn:</strong> 
                                    {{ $dt->gv_huongdan }}
                                </p>

                                <hr>

                                <strong>Sinh viên thực hiện:</strong>
                                <ul class="mt-2">
                                    @foreach($sinhVienTheoNhom[$dt->nhom_id] as $sv)
                                    <li>
                                        <strong>{{ $sv->hoten }}</strong> - MSSV: {{ $sv->mssv }} - Lớp: {{ $sv->lop }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection