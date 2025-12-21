@extends('layouts.app')

@section('header', 'Chấm Điểm Giữa Kỳ')

@section('content')
<div class="container-fluid py-4">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">
                <i class="fa fa-clipboard-check me-2 text-primary"></i>Chấm Điểm Giữa Kỳ
            </h3>
            <p class="text-muted mb-0">Đánh giá tiến độ và chất lượng đồ án của sinh viên</p>
        </div>
        @if(count($groupedStudents) > 0)
            <div class="d-flex gap-2">
                <a href="{{ route('lecturers.diemgiuaky.export') }}" class="btn btn-outline-success btn-lg shadow-sm">
                    <i class="fa fa-file-excel me-2"></i>Xuất Excel
                </a>
                <button form="diemForm" type="submit" class="btn btn-success btn-lg shadow-sm">
                    <i class="fa fa-save me-2"></i>Lưu Đánh Giá
                </button>
            </div>
        @endif
    </div>

    {{-- ALERTS --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            @foreach($errors->all() as $error)
                <div><i class="fa fa-times-circle me-2"></i>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FORM --}}
    <form action="{{ route('lecturers.diemgiuaky.store') }}" method="POST" id="diemForm">
        @csrf
        
        @if(count($groupedStudents) > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th width="8%" class="text-center">Nhóm</th>
                                    <th width="10%">MSSV</th>
                                    <th width="15%">Họ Tên</th>
                                    <th width="27%">Đề Tài</th>
                                    <th width="8%" class="text-center">Điểm</th>
                                    <th width="12%">Kết Quả</th>
                                    <th width="20%" class="text-center">Nhận Xét</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupedStudents as $groupIndex => $group)
                                    @php $studentCount = count($group['students']); @endphp
                                    @foreach($group['students'] as $index => $sv)
                                        <tr class="{{ $groupIndex > 0 && $index === 0 ? 'group-separator' : '' }}">
                                            {{-- NHÓM --}}
                                            @if($index === 0)
                                                <td rowspan="{{ $studentCount }}" class="text-center align-middle group-cell">
                                                    @if($group['nhom'] && $group['nhom'] !== 'Chưa có')
                                                        <span class="badge bg-primary">{{ $group['nhom'] }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                            
                                            {{-- MSSV --}}
                                            <td>
                                                <strong class="text-primary">{{ $sv['mssv'] }}</strong>
                                                <input type="hidden" name="mssv[]" value="{{ $sv['mssv'] }}">
                                            </td>
                                            
                                            {{-- TÊN SINH VIÊN --}}
                                            <td>
                                                {{ $sv['tensv'] }}
                                            </td>
                                            
                                            {{-- ĐỀ TÀI --}}
                                            <td>
                                                @if($group['tendt'])
                                                    <strong class="text-dark">{{ Str::limit($group['tendt'], 60) }}</strong>
                                                @else
                                                    <span class="text-danger fst-italic">Chưa có đề tài</span>
                                                @endif
                                            </td>
                                            
                                            {{-- ĐIỂM --}}
                                            <td>
                                                <input type="number" 
                                                       name="diem[{{ $sv['mssv'] }}]" 
                                                       class="form-control form-control-sm text-center fw-bold score-input" 
                                                       value="{{ $sv['diem'] }}"
                                                       min="0" 
                                                       max="10" 
                                                       step="0.25"
                                                       placeholder="0-10">
                                            </td>
                                            
                                            {{-- KẾT QUẢ --}}
                                            <td>
                                                <select name="ketqua[{{ $sv['mssv'] }}]" class="form-select form-select-sm status-select">
                                                    <option value="chua_danh_gia" {{ $sv['ketqua'] == 'chua_danh_gia' || !$sv['ketqua'] ? 'selected' : '' }}>
                                                        Chưa đánh giá
                                                    </option>
                                                    <option value="duoc_tieptuc" {{ $sv['ketqua'] == 'duoc_tieptuc' ? 'selected' : '' }}>
                                                        ✅ Được tiếp tục
                                                    </option>
                                                    <option value="khong_duoc_tieptuc" {{ $sv['ketqua'] == 'khong_duoc_tieptuc' ? 'selected' : '' }}>
                                                        ❌ Không tiếp tục
                                                    </option>
                                                </select>
                                            </td>
                                            
                                            {{-- NHẬN XÉT --}}
                                            <td>
                                                <textarea name="nhanxet[{{ $sv['mssv'] }}]" 
                                                          class="form-control form-control-sm" 
                                                          rows="2" 
                                                          placeholder="Nhận xét...">{{ $sv['nhanxet'] }}</textarea>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- HƯỚNG DẪN --}}
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fa fa-info-circle text-primary me-2"></i>Hướng Dẫn Chấm Điểm
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-check-circle text-success me-2"></i>
                                <small>Điểm từ 0 đến 10 (có thể nhập: 7.5, 8.25)</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-check-circle text-success me-2"></i>
                                <small>Trạng thái tự động chọn theo điểm</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-check-circle text-success me-2"></i>
                                <small>Điểm ≥ 5: Được tiếp tục | < 5: Không tiếp tục</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- EMPTY STATE --}}
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fa fa-clipboard-list fa-5x text-muted opacity-50"></i>
                </div>
                <h4 class="text-muted mb-3">Chưa có sinh viên nào</h4>
                <p class="text-muted">Không có sinh viên được phân công cho bạn</p>
            </div>
        @endif
    </form>
</div>

<style>
/* TABLE STYLING */
.table {
    font-size: 0.95rem;
}

.table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.table thead th {
    padding: 1.2rem 1rem;
    font-weight: 700;
    font-size: 1rem;
    border: none;
    vertical-align: middle;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
}

/* BỎ HOÀN TOÀN HOVER EFFECT */

/* LINE ĐẬM GIỮA CÁC NHÓM */
.table tbody tr.group-separator td {
    border-top: 3px solid #dee2e6;
}

/* GROUP CELL - ĐƠN GIẢN */
.group-cell {
    background: #f8f9fa;
}

/* FORM CONTROLS */
.form-control,
.form-select {
    border-radius: 6px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

.form-control-sm,
.form-select-sm {
    padding: 0.4rem 0.6rem;
    font-size: 0.875rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* ĐIỂM INPUT - NHỎ GỌN */
.score-input {
    font-weight: 700 !important;
    color: #e91e63;
    width: 80px;
    margin: 0 auto;
}

/* STATUS SELECT */
.status-select {
    font-weight: 500;
    font-size: 0.875rem;
}

/* TEXTAREA NHẬN XÉT */
textarea.form-control {
    resize: vertical;
    min-height: 50px;
}

/* BADGES */
.badge {
    font-weight: 500;
    font-size: 0.85rem;
    border-radius: 6px;
}

/* CARDS */
.card {
    border-radius: 12px;
    overflow: hidden;
}

/* BUTTONS */
.btn {
    border-radius: 8px;
    padding: 0.6rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
}

/* ALERTS */
.alert {
    border-radius: 10px;
    border: none;
}

/* SCROLLBAR */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .table {
        font-size: 0.85rem;
    }
    
    .table thead th,
    .table tbody td {
        padding: 0.75rem 0.5rem;
    }
    
    .group-badge,
    .info-badge {
        font-size: 0.9rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate status based on score
    const scoreInputs = document.querySelectorAll('input[type="number"]');
    
    scoreInputs.forEach(input => {
        input.addEventListener('change', function() {
            const mssv = this.name.match(/\[(.*?)\]/)[1];
            const score = parseFloat(this.value);
            const ketquaSelect = document.querySelector(`select[name="ketqua[${mssv}]"]`);
            
            if (!isNaN(score)) {
                if (score >= 5) {
                    ketquaSelect.value = 'duoc_tieptuc';
                } else if (score > 0) {
                    ketquaSelect.value = 'khong_duoc_tieptuc';
                }
            }
        });
    });
    
    // Confirm before submit
    document.getElementById('diemForm').addEventListener('submit', function(e) {
        const filledScores = Array.from(scoreInputs).filter(input => input.value).length;
        
        if (filledScores === 0) {
            e.preventDefault();
            alert('Vui lòng nhập ít nhất 1 điểm trước khi lưu!');
            return false;
        }
        
        if (!confirm(`Bạn đã chấm điểm cho ${filledScores} sinh viên. Xác nhận lưu đánh giá?`)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endsection