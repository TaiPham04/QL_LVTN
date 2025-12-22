@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <form action="{{ route('lecturers.cham-diem.hoi-dong.store', $mahd) }}" method="POST" id="assignmentForm">
        @csrf
        
        <div class="row">
            <div class="col-12">
                {{-- Header --}}
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fa fa-star me-2"></i> Chấm Điểm - {{ $hoiDong->tenhd }}
                        </h4>
                        <a href="{{ route('lecturers.cham-diem.hoi-dong.index') }}" class="btn btn-light btn-sm">
                            <i class="fa fa-arrow-left me-1"></i> Quay Lại
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>Mã hội đồng:</strong> <span class="badge bg-primary">{{ $hoiDong->mahd }}</span></p>
                                <p><strong>Vai trò của bạn:</strong>
                                    @if($vaiTroGV == 'chu_tich')
                                        <span class="badge bg-warning">👑 Chủ tịch</span>
                                    @elseif($vaiTroGV == 'thu_ky')
                                        <span class="badge bg-info">📋 Thư ký</span>
                                    @else
                                        <span class="badge bg-secondary">👤 Thành viên</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Số đề tài:</strong> <span class="badge bg-cyan">{{ count($deTaiList) }} đề tài</span></p>
                                <p><strong>Quyền chấm:</strong>
                                    @if(in_array($vaiTroGV, ['chu_tich', 'thu_ky']))
                                        <span class="badge bg-success">✓ Có quyền</span>
                                    @else
                                        <span class="badge bg-danger">✗ Chỉ xem</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Thành viên hội đồng:</strong></p>
                                <div class="small">
                                    @foreach($thanhVien as $tv)
                                        <div>
                                            @if($tv->vai_tro === 'chu_tich')
                                                👑
                                            @elseif($tv->vai_tro === 'thu_ky')
                                                📋
                                            @else
                                                👤
                                            @endif
                                            {{ $tv->hoten }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Danh sách đề tài --}}
                @forelse($deTaiList as $deTai)
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fa fa-file-alt me-2"></i>{{ $deTai->nhom }} - {{ $deTai->tendt }}
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="8%">MSSV</th>
                                            <th width="20%">Tên Sinh Viên</th>
                                            <th width="8%">Lớp</th>
                                            @foreach($thanhVien as $tv)
                                                @php
                                                    $parts = explode(' ', trim($tv->hoten));
                                                    $abbreviated = count($parts) > 1 
                                                        ? strtoupper($parts[0][0]) . '. ' . implode(' ', array_slice($parts, 1))
                                                        : $tv->hoten;
                                                @endphp
                                                <th width="8%" class="text-center" title="{{ $tv->hoten }}">
                                                    <span class="d-block" style="font-size: 0.75rem; line-height: 1.2;">
                                                        {{ $abbreviated }}
                                                    </span>
                                                    @if($tv->vai_tro === 'chu_tich')
                                                        <i class="fa fa-crown text-warning" style="font-size: 0.7rem;"></i>
                                                    @elseif($tv->vai_tro === 'thu_ky')
                                                        <i class="fa fa-file-lines text-info" style="font-size: 0.7rem;"></i>
                                                    @endif
                                                </th>
                                            @endforeach
                                            <th width="8%" class="text-center">Điểm TB</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($sinhVienNhom[$deTai->nhom_id] as $sv)
                                            <tr>
                                                <td><strong>{{ $sv->mssv }}</strong></td>
                                                <td>{{ $sv->hoten }}</td>
                                                <td>{{ $sv->lop }}</td>
                                                
                                                @php
                                                    $diemKey = $deTai->nhom_id . '_' . $sv->mssv;
                                                    $diemSinhVien = $diemHienTai->get($diemKey, []);
                                                    $tongDiem = 0;
                                                    $diemCount = 0;
                                                @endphp

                                                @foreach($thanhVien as $tv)
                                                    <td class="text-center">
                                                        @php
                                                            $diem = $diemSinhVien[$tv->magv]['diem'] ?? '';
                                                            if ($diem) {
                                                                $tongDiem += floatval($diem);
                                                                $diemCount++;
                                                            }
                                                        @endphp
                                                        
                                                        @if(in_array($vaiTroGV, ['chu_tich', 'thu_ky']))
                                                            <input type="number" step="0.01" min="0" max="10"
                                                                   name="diem[{{ $deTai->nhom_id }}_{{ $sv->mssv }}][{{ $tv->magv }}]"
                                                                   class="form-control form-control-sm text-center diem-input"
                                                                   value="{{ $diem }}"
                                                                   data-nhom="{{ $deTai->nhom_id }}"
                                                                   data-mssv="{{ $sv->mssv }}">
                                                        @else
                                                            <div class="form-control form-control-sm text-center bg-light">
                                                                {{ $diem ?: '-' }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                @endforeach

                                                <td class="text-center fw-bold">
                                                    @if($diemCount > 0)
                                                        <span class="diem-trungbinh" data-nhom="{{ $deTai->nhom_id }}" data-mssv="{{ $sv->mssv }}">
                                                            {{ round($tongDiem / count($thanhVien), 2) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ count($thanhVien) + 4 }}" class="text-center py-3 text-muted">
                                                    Không có sinh viên
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-warning text-center">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        Không có đề tài nào được phân công
                    </div>
                @endforelse

                {{-- Nút Hành Động --}}
                @if(in_array($vaiTroGV, ['chu_tich', 'thu_ky']))
                    <div class="card">
                        <div class="card-body text-end">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-save me-2"></i>Lưu Điểm
                            </button>
                            <a href="{{ route('lecturers.cham-diem.hoi-dong.index') }}" class="btn btn-secondary btn-lg">
                                <i class="fa fa-times me-2"></i>Hủy
                            </a>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="fa fa-info-circle me-2"></i>
                        Bạn là thành viên - chỉ có quyền xem, không thể chỉnh sửa điểm
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>

<style>
    .bg-cyan {
        background-color: #0dcaf0 !important;
    }

    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
        white-space: nowrap;
    }

    .table td {
        vertical-align: middle;
    }

    .form-control-sm {
        padding: 0.4rem;
        font-size: 0.875rem;
    }

    .badge {
        font-weight: 500;
        padding: 0.4em 0.7em;
    }

    .card {
        border-radius: 8px;
        border: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .card-header {
        border-radius: 8px 8px 0 0 !important;
    }

    .btn {
        border-radius: 6px;
        padding: 0.5rem 1rem;
    }

    .diem-input {
        border-color: #dee2e6;
    }

    .diem-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    .diem-trungbinh {
        font-size: 1.1em;
        color: #0d6efd;
    }
</style>

<script>
    // Helper function: Viết tắt tên giảng viên
    function abbreviateName(fullName) {
        const parts = fullName.trim().split(' ');
        if (parts.length <= 1) {
            return fullName;
        }
        
        const firstName = parts[0];
        const lastName = parts.slice(1).join(' ');
        
        return firstName.charAt(0).toUpperCase() + '. ' + lastName;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const diemInputs = document.querySelectorAll('.diem-input');

        diemInputs.forEach(input => {
            input.addEventListener('input', function() {
                updateDiemTrungBinh(this);
            });
        });

        function updateDiemTrungBinh(inputEl) {
            const nhom = inputEl.dataset.nhom;
            const mssv = inputEl.dataset.mssv;

            // Lấy tất cả input điểm cho sinh viên này
            const allInputs = document.querySelectorAll(
                `.diem-input[data-nhom="${nhom}"][data-mssv="${mssv}"]`
            );

            let tongDiem = 0;
            let count = 0;

            allInputs.forEach(inp => {
                if (inp.value) {
                    tongDiem += parseFloat(inp.value);
                    count++;
                }
            });

            // Cập nhật điểm trung bình
            const trungBinhEl = document.querySelector(
                `.diem-trungbinh[data-nhom="${nhom}"][data-mssv="${mssv}"]`
            );

            if (trungBinhEl) {
                if (count > 0) {
                    const trungBinh = (tongDiem / count).toFixed(2);
                    trungBinhEl.textContent = trungBinh;
                } else {
                    trungBinhEl.innerHTML = '<span class="text-muted">-</span>';
                }
            }
        }

        // Validate form trước submit
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            const hasDiem = Array.from(diemInputs).some(inp => inp.value);
            
            if (!hasDiem) {
                e.preventDefault();
                alert('Vui lòng nhập ít nhất 1 điểm!');
                return false;
            }

            if (!confirm('Xác nhận lưu điểm?')) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>

@endsection