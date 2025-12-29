@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fa fa-star me-2"></i>Chấm Điểm Hội Đồng
            </h5>
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

            {{-- Danh Sách Hội Đồng --}}
            @if($khongCoHoiDong)
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Bạn chưa có trong hội đồng nào</h5>
                    <p class="text-muted">Vui lòng đợi được phân công vào hội đồng</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">STT</th>
                                <th style="width: 20%">Mã Hội Đồng</th>
                                <th style="width: 25%">Tên Hội Đồng</th>
                                <th style="width: 15%">Vai Trò</th>
                                <th style="width: 12%" class="text-center">Đề Tài</th>
                                <th style="width: 12%" class="text-center">Trạng Thái</th>
                                <th style="width: 11%" class="text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($danhSachHoiDong as $index => $hd)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $hd->mahd }}</span>
                                    </td>
                                    <td><strong>{{ $hd->tenhd }}</strong></td>
                                    <td class="text-center">
                                        @if($hd->vai_tro === 'chu_tich')
                                            <span class="badge bg-warning">👑 Chủ tịch</span>
                                        @elseif($hd->vai_tro === 'thu_ky')
                                            <span class="badge bg-info">📋 Thư ký</span>
                                        @else
                                            <span class="badge bg-secondary">👤 Thành viên</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-cyan">{{ $hd->so_detai }} đề tài</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">✓ Đang mở</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            {{-- Nút Chấm Điểm --}}
                                            <a href="{{ route('lecturers.cham-diem.hoi-dong.form', $hd->mahd) }}"
                                               class="btn btn-primary btn-sm"
                                               title="Chấm điểm">
                                                <i class="fa fa-pen"></i>
                                            </a>

                                            {{-- Nút Xuất Excel (Chỉ Chủ Tịch & Thư Ký) --}}
                                            @if(in_array($hd->vai_tro, ['chu_tich', 'thu_ky']))
                                                @php
                                                    // Kiểm tra đề tài đã chấm hết chưa
                                                    $soThanhVien = DB::table('thanhvienhoidong')
                                                        ->where('hoidong_id', $hd->hoidong_id)
                                                        ->count();
                                                    
                                                    $deTaiList = DB::table('hoidong_detai as hdt')
                                                        ->join('nhom as n', 'hdt.nhom_id', '=', 'n.id')
                                                        ->where('hdt.hoidong_id', $hd->hoidong_id)
                                                        ->select('n.id as nhom_id')
                                                        ->distinct()
                                                        ->get();

                                                    $uncheckedCount = 0;
                                                    foreach ($deTaiList as $deTai) {
                                                        $sinhVienList = DB::table('detai')
                                                            ->where('nhom_id', $deTai->nhom_id)
                                                            ->distinct('mssv')
                                                            ->count();

                                                        $diemCount = DB::table('hoidong_chamdiem')
                                                            ->where('hoidong_id', $hd->hoidong_id)
                                                            ->where('nhom_id', $deTai->nhom_id)
                                                            ->distinct('mssv')
                                                            ->count();

                                                        if ($diemCount < $sinhVienList) {
                                                            $uncheckedCount++;
                                                        }
                                                    }

                                                    $canExport = $uncheckedCount === 0 && !$deTaiList->isEmpty();
                                                @endphp

                                                @if($canExport)
                                                    <a href="{{ route('lecturers.cham-diem.hoi-dong.export-excel', $hd->mahd) }}"
                                                       class="btn btn-success btn-sm"
                                                       title="Xuất Excel">
                                                        <i class="fa fa-file-excel"></i>
                                                    </a>
                                                @else
                                                    <button class="btn btn-secondary btn-sm" disabled 
                                                            title="Chưa chấm hết đề tài ({{ $uncheckedCount }}/{{ $deTaiList->count() }})">
                                                        <i class="fa fa-file-excel"></i>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Không có hội đồng nào
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
        border-top: 2px solid #dee2e6;
    }

    .table td {
        vertical-align: middle;
    }

    .badge {
        font-weight: 500;
        padding: 0.4em 0.7em;
    }

    .bg-cyan {
        background-color: #0dcaf0 !important;
    }

    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }

    .gap-2 {
        gap: 0.5rem !important;
    }

    .alert {
        border-radius: 8px;
        border: none;
    }

    /* Làm sáng icon khi disabled */
    .btn:disabled {
        opacity: 0.7;
    }

    .btn-warning:disabled {
        background-color: #ffc107 !important;
        color: #000 !important;
    }
</style>

@endsection