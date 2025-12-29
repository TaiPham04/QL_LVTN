@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h4 class="mb-4"><i class="fas fa-clipboard-list me-2 text-primary"></i>Bảng Tổng Kết</h4>

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

    {{-- ✅ TOOLBAR --}}
    <div class="card shadow-sm rounded-3 mb-3">
        <div class="card-header bg-primary text-white py-2">
            <i class="fas fa-tools me-2"></i>Công cụ
        </div>
        <div class="card-body border-bottom p-3" style="background: #f8f9fa;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                {{-- Dropdown ẩn/hiện cột --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                        <i class="fas fa-eye me-1"></i>Hiển thị/Ẩn cột
                    </button>
                    <ul class="dropdown-menu p-2" style="min-width: 200px;">
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="1" data-col-name="mssv" checked> MSSV
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="2" data-col-name="tennhom" checked> Nhóm
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="3" data-col-name="hoten" checked> Tên Sinh Viên
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="4" data-col-name="lop" checked> LỚP
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="5" data-col-name="tendt" checked> Đề tài
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="6" data-col-name="ten_gvhd" checked> GV Hướng dẫn
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="7" data-col-name="diem_gvhd" checked> Điểm GVHD
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="8" data-col-name="ten_gvpb" checked> GV Phản biện
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="9" data-col-name="diem_gvpb" checked> Điểm GVPB
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="10" data-col-name="diem_hoidong" checked> Điểm HD
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="11" data-col-name="diem_tong" checked> Điểm Tổng
                            </label>
                        </li>
                    </ul>
                </div>

                {{-- Ô tìm kiếm --}}
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 fw-bold small">Tìm kiếm:</label>
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="MSSV, tên, nhóm, đề tài..." style="width: 280px;">
                </div>

                {{-- Xuất Excel - Click để export cột hiển thị --}}
                <button class="btn btn-sm btn-success" onclick="exportExcel()">
                    <i class="fas fa-file-excel me-1"></i>Xuất Excel
                </button>

                {{-- Thống kê --}}
                <div class="bg-light rounded px-3 py-2 text-center">
                    <i class="fas fa-list text-primary"></i>
                    <strong>Tổng:</strong> <span id="totalCount">{{ $students->count() }}</span> sinh viên
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ BẢNG DANH SÁCH --}}
    <div class="card shadow-sm rounded-3">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-table me-2"></i>Danh sách tổng kết</span>
            <span class="badge bg-light text-dark">Tổng: {{ $students->count() }} dòng</span>
        </div>

        <div class="card-body p-0" style="max-height: 700px; overflow-y: auto; overflow-x: auto; min-width: 0;">
            <table id="bangTongKetTable" class="table table-hover table-bordered mb-0" style="min-width: 1400px;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="checkbox-col text-center">
                            <input type="checkbox" id="checkAll" class="form-check-input">
                        </th>
                        <th data-col="1">MSSV</th>
                        <th data-col="2">Nhóm</th>
                        <th data-col="3">Tên Sinh Viên</th>
                        <th data-col="4">LỚP</th>
                        <th data-col="5">Đề tài</th>
                        <th data-col="6">GV Hướng dẫn</th>
                        <th data-col="7">Điểm GVHD</th>
                        <th data-col="8">GV Phản biện</th>
                        <th data-col="9">Điểm GVPB</th>
                        <th data-col="10">Điểm HD</th>
                        <th data-col="11">Điểm Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input row-checkbox" value="{{ $student->mssv }}">
                            </td>
                            <td data-col="1">
                                <strong>{{ $student->mssv }}</strong>
                            </td>
                            <td class="text-center" data-col="2">
                                <span class="badge bg-primary">{{ $student->tennhom ?? 'N/A' }}</span>
                            </td>
                            <td data-col="3">{{ $student->hoten }}</td>
                            <td class="text-center" data-col="4">{{ $student->lop ?? 'N/A' }}</td>
                            <td data-col="5">
                                <small class="text-muted">{{ Str::limit($student->tendt ?? 'Chưa có', 25) }}</small>
                            </td>
                            <td data-col="6">
                                <small>{{ $student->ten_gvhd ?? '-' }}</small>
                            </td>
                            <td class="text-center" data-col="7">
                                @if($student->diem_gvhd && $student->diem_gvhd > 0)
                                    <span class="badge bg-info">{{ $student->diem_gvhd }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td data-col="8">
                                <small>{{ $student->ten_gvpb ?? '-' }}</small>
                            </td>
                            <td class="text-center" data-col="9">
                                @if($student->diem_gvpb && $student->diem_gvpb > 0)
                                    <span class="badge bg-info">{{ $student->diem_gvpb }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center" data-col="10">
                                @if($student->diem_hoidong && $student->diem_hoidong > 0)
                                    <span class="badge bg-warning text-dark">{{ $student->diem_hoidong }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center" data-col="11">
                                @if($student->diem_tong && $student->diem_tong > 0)
                                    @php
                                        $diem = (float)$student->diem_tong;
                                        if ($diem >= 8.5) $class = 'bg-success';
                                        elseif ($diem >= 7) $class = 'bg-info';
                                        elseif ($diem >= 5) $class = 'bg-warning text-dark';
                                        else $class = 'bg-danger';
                                    @endphp
                                    <span class="badge {{ $class }} fs-6">{{ $student->diem_tong }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x d-block mb-2"></i>
                                Không có dữ liệu
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* ✅ CHECKBOX COLUMN - CỨNG 30px */
    .checkbox-col {
        width: 30px !important;
        max-width: 30px !important;
        min-width: 30px !important;
        padding: 8px 4px !important;
        text-align: center !important;
        flex: 0 0 30px !important;
    }
    
    #bangTongKetTable tbody td:first-child {
        width: 30px !important;
        max-width: 30px !important;
        min-width: 30px !important;
        padding: 8px 4px !important;
        text-align: center !important;
        flex: 0 0 30px !important;
    }
    
    /* ✅ TABLE */
    #bangTongKetTable {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
    }
    
    /* ✅ HEADER - KHÔNG CẮT TEXT */
    #bangTongKetTable thead {
        background-color: #f8f9fa;
    }
    
    #bangTongKetTable thead th {
        background-color: #f8f9fa !important;
        font-weight: 600;
        padding: 12px 15px !important;
        border: 1px solid #dee2e6 !important;
        text-align: left !important;
        min-width: fit-content !important;
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 14px !important;
        letter-spacing: normal !important;
        overflow: visible !important;
    }
    
    /* ✅ BODY */
    #bangTongKetTable tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05) !important;
    }
    
    #bangTongKetTable tbody td {
        padding: 8px 12px !important;
        border: 1px solid #dee2e6 !important;
        vertical-align: middle !important;
        word-break: break-word;
        overflow-wrap: break-word;
        white-space: normal !important;
    }
    
    /* ✅ GENERAL */
    .table {
        margin-bottom: 0 !important;
        width: 100% !important;
    }
    
    .table-light {
        background-color: #f8f9fa !important;
    }
    
    #searchInput:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .dropdown-menu {
        z-index: 1050;
    }
    
    .dropdown-menu label.dropdown-item {
        cursor: pointer;
        padding: 8px 16px;
        user-select: none;
        margin-bottom: 0;
    }
    
    .dropdown-menu label.dropdown-item:hover {
        background-color: #f8f9fa;
    }
</style>

<script>
// ✅ Dữ liệu gốc từ server
const originalStudents = @json($students);

document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('bangTongKetTable');
    const searchInput = document.getElementById('searchInput');
    const rows = table.querySelectorAll('tbody tr');
    
    // ✅ Lưu trạng thái visibility của các cột
    const columnVisibility = {1: true, 2: true, 3: true, 4: true, 5: true, 6: true, 7: true, 8: true, 9: true, 10: true, 11: true};

    // ✅ TÌM KIẾM
    searchInput.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase().trim();
        
        rows.forEach(row => {
            let found = false;
            const cells = row.querySelectorAll('td');
            
            // Kiểm tra từng cell (bỏ qua checkbox ở index 0)
            for (let i = 1; i < cells.length; i++) {
                // Chỉ tìm trong cột đang visible
                if (columnVisibility[i]) {
                    const cellText = cells[i].textContent.toLowerCase();
                    if (cellText.includes(searchValue)) {
                        found = true;
                        break;
                    }
                }
            }
            
            row.style.display = found || searchValue === '' ? '' : 'none';
        });
    });

    // ✅ ẨN/HIỆN CỘT
    document.querySelectorAll('.col-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const colIndex = parseInt(this.dataset.col);
            const isVisible = this.checked;
            
            columnVisibility[colIndex] = isVisible;
            
            // Ẩn/hiện header
            const headerCells = table.querySelectorAll('thead th');
            if (headerCells[colIndex]) {
                headerCells[colIndex].style.display = isVisible ? '' : 'none';
            }
            
            // Ẩn/hiện cells trong mỗi row
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells[colIndex]) {
                    cells[colIndex].style.display = isVisible ? '' : 'none';
                }
            });
            
            // Re-apply search filter
            searchInput.dispatchEvent(new Event('keyup'));
        });
    });

    // ✅ CHỌN TẤT CẢ
    document.getElementById('checkAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const visibleCheckboxes = Array.from(checkboxes).filter(chk => {
            return chk.closest('tr').style.display !== 'none';
        });
        
        visibleCheckboxes.forEach(chk => chk.checked = this.checked);
        updateSelectedCount();
    });

    // ✅ CHECKBOX INDIVIDUAL
    document.querySelectorAll('.row-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
        });
    });

    // ✅ CẬP NHẬT SỐ LƯỢNG ĐÃ CHỌN
    function updateSelectedCount() {
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        document.getElementById('totalCount').textContent = count;
    }
    
    // ✅ EXPORT EXCEL - lấy cột hiển thị
    window.exportExcel = function() {
        // ✅ Lấy danh sách cột hiển thị
        const visibleCols = [];
        document.querySelectorAll('.col-toggle:checked').forEach(checkbox => {
            visibleCols.push(checkbox.dataset.colName);
        });
        
        // ✅ Lấy dữ liệu hiển thị trên table (chỉ những dòng không bị ẩn)
        const visibleMSSVs = [];
        document.querySelectorAll('#bangTongKetTable tbody tr').forEach(row => {
            if (row.style.display !== 'none') {
                const mssv = row.querySelector('[data-col="1"]')?.textContent?.trim();
                if (mssv) {
                    visibleMSSVs.push(mssv);
                }
            }
        });
        
        // ✅ Xây dựng URL export
        let exportUrl = `{{ route('admin.bang-tong-ket.export') }}?visible_columns=${visibleCols.join(',')}`;
        
        // Nếu có filter (search, tìm kiếm, hoặc ẩn cột), gửi danh sách MSSV
        if (visibleMSSVs.length > 0 && visibleMSSVs.length < document.querySelectorAll('#bangTongKetTable tbody tr').length) {
            exportUrl += `&mssv_filter=${visibleMSSVs.join(',')}`;
        }
        
        // Chuyển hướng tới URL export
        window.location.href = exportUrl;
    };
});
</script>

@endsection