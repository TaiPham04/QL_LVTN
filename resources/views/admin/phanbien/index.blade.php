@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h4 class="mb-4">Phân công phản biện theo nhóm</h4>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form id="phancong-form" action="{{ route('admin.phanbien.store') }}" method="POST">
        @csrf

        {{-- ✅ PANEL CHỌN GIẢNG VIÊN - ĐẨY LÊN TRÊN --}}
        <div class="card shadow-sm rounded-3 mb-3">
            <div class="card-header bg-success text-white py-2">
                <i class="bi bi-person-check-fill me-2"></i>Chọn giảng viên phản biện
            </div>
            <div class="card-body py-3">
                <div class="row align-items-end g-3">
                    <div class="col-lg-4 col-md-6">
                        <div class="d-flex align-items-center text-muted small">
                            <i class="bi bi-info-circle text-info me-2 fs-5"></i>
                            <span>
                                <strong>Hướng dẫn:</strong> 
                                1. Chọn nhóm → 2. Chọn GV phản biện → 3. Lưu phân công
                            </span>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="magv_phanbien" class="form-label mb-1 small fw-bold">
                            <i class="bi bi-person-circle text-primary"></i> Giảng viên phản biện <span class="text-danger">*</span>
                        </label>
                        <select name="magv_phanbien" id="magv_phanbien" class="form-select" required>
                            <option value="">-- Chọn giảng viên --</option>
                            @foreach ($giangviens as $gv)
                            <option value="{{ $gv->magv }}">{{ $gv->hoten }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Lưu phân công
                            </button>
                            <a href="#" onclick="exportExcel(event)" class="btn btn-success">
                                <i class="bi bi-file-earmark-excel me-1"></i>Xuất Excel
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="bg-light rounded px-3 py-2 text-center">
                            <i class="bi bi-check-square text-success"></i>
                            <strong>Đã chọn:</strong> <span id="selectedCount">0</span> nhóm
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ BẢNG DANH SÁCH NHÓM --}}
        <div class="card shadow-sm rounded-3">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-folder2-open me-2"></i>Danh sách nhóm đề tài</span>
                <span class="badge bg-light text-dark">Tổng: {{ count($groupedTopics) }} nhóm</span>
            </div>

            {{-- ✅ CUSTOM TOOLBAR - TỰ TẠO TRONG HTML --}}
            <div class="card-body border-bottom p-3" style="background: #f8f9fa;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    {{-- Dropdown ẩn/hiện cột --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="bi bi-eye-fill me-1"></i>Hiển thị/Ẩn cột
                        </button>
                        <ul class="dropdown-menu p-2" style="min-width: 180px;">
                            <li>
                                <label class="dropdown-item d-flex align-items-center">
                                    <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="1" checked> Nhóm
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item d-flex align-items-center">
                                    <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="2" checked> MSSV
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item d-flex align-items-center">
                                    <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="3" checked> Tên Sinh Viên
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item d-flex align-items-center">
                                    <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="4" checked> Đề tài
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item d-flex align-items-center">
                                    <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="5" checked> GVHD
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item d-flex align-items-center">
                                    <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="6" checked> GV Phản biện
                                </label>
                            </li>
                        </ul>
                    </div>

                    {{-- Ô tìm kiếm --}}
                    <div class="d-flex align-items-center gap-2">
                        <label class="mb-0 fw-bold small">Tìm kiếm:</label>
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Nhóm, MSSV, đề tài, giảng viên..." style="width: 280px;">
                    </div>
                </div>
            </div>

            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto; overflow-x: visible;">
                <table id="phanBienTable" class="table table-hover table-bordered mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="text-center" style="width: 50px;">
                                <input type="checkbox" id="checkAll" class="form-check-input">
                            </th>
                            <th style="width: 80px;">Nhóm</th>
                            <th style="width: 100px;">MSSV</th>
                            <th style="width: 150px;">Tên Sinh Viên</th>
                            <th>Đề tài</th>
                            <th style="width: 150px;">GVHD</th>
                            <th style="width: 150px;">GV Phản biện</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groupedTopics as $topic)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" 
                                       name="selected_topics[]" 
                                       value="{{ $topic->nhom_id }}" 
                                       class="form-check-input topic-checkbox"
                                       data-magv-hd="{{ $topic->magv_hd }}">
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $topic->nhom }}</span>
                                <br>
                                <small class="text-muted">({{ $topic->soluong_sv }} SV)</small>
                            </td>
                            <td>
                                @foreach($topic->sinhvien as $sv)
                                    <div class="mb-1">
                                        <small><strong>{{ $sv['mssv'] }}</strong></small>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                @foreach($topic->sinhvien as $sv)
                                    <div class="mb-1">
                                        <small class="text-muted">{{ $sv['tensv'] }}</small>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                <small><strong>{{ $topic->tendt }}</strong></small>
                            </td>
                            <td class="text-center">
                                <small class="text-primary">
                                    <i class="bi bi-person-badge"></i> {{ $topic->tengv_hd ?? 'Chưa có' }}
                                </small>
                            </td>
                            <td class="text-center">
                                @if($topic->magv_phanbien)
                                    <span class="badge bg-success mb-1">
                                        <i class="bi bi-check-circle"></i> Đã phân
                                    </span>
                                    <br>
                                    <small class="text-success">{{ $topic->tengv_phanbien }}</small>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-clock"></i> Chưa phân
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Chưa có nhóm nào
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<style>
    .table thead {
        background-color: #f8f9fa !important;
    }
    .table tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05) !important;
    }
    #searchInput:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25) !important;
    }
    
    /* ✅ Fix dropdown z-index */
    .dropdown-menu {
        z-index: 1050 !important;
    }
    
    /* ✅ Fix dropdown item style */
    .dropdown-menu label.dropdown-item {
        cursor: pointer;
        padding: 8px 16px;
    }
    .dropdown-menu label.dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    /* ✅ Fix table container không che dropdown */
    .card-body.p-0 {
        position: relative;
        z-index: 1;
    }
    
    /* ✅ Fix toolbar z-index cao hơn */
    .card-body.border-bottom {
        position: relative;
        z-index: 1060;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('phanBienTable');
    const searchInput = document.getElementById('searchInput');
    const rows = table.querySelectorAll('tbody tr');
    
    // ✅ Lưu trạng thái visibility của các cột
    const columnVisibility = {1: true, 2: true, 3: true, 4: true, 5: true, 6: true};

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

    // ✅ KHÔI PHỤC STATE
    const savedGV = sessionStorage.getItem('selected_gv');
    if (savedGV) {
        document.getElementById('magv_phanbien').value = savedGV;
    }
    
    const savedCheckboxes = JSON.parse(sessionStorage.getItem('selected_topics') || '[]');
    savedCheckboxes.forEach(nhomId => {
        const checkbox = document.querySelector(`input.topic-checkbox[value="${nhomId}"]`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    
    updateSelectedCount();

    // ✅ LƯU GIẢNG VIÊN KHI THAY ĐỔI
    document.getElementById('magv_phanbien').addEventListener('change', function() {
        sessionStorage.setItem('selected_gv', this.value);
    });

    // ✅ LƯU CHECKBOX KHI THAY ĐỔI
    document.querySelectorAll('.topic-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectedIds = Array.from(document.querySelectorAll('.topic-checkbox:checked'))
                .map(chk => chk.value);
            sessionStorage.setItem('selected_topics', JSON.stringify(selectedIds));
            updateSelectedCount();
        });
    });

    // ✅ CHỌN TẤT CẢ
    document.getElementById('checkAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.topic-checkbox');
        const visibleCheckboxes = Array.from(checkboxes).filter(chk => {
            return chk.closest('tr').style.display !== 'none';
        });
        
        visibleCheckboxes.forEach(chk => chk.checked = this.checked);
        
        const selectedIds = Array.from(document.querySelectorAll('.topic-checkbox:checked'))
            .map(chk => chk.value);
        sessionStorage.setItem('selected_topics', JSON.stringify(selectedIds));
        updateSelectedCount();
    });

    // ✅ SUBMIT FORM
    document.getElementById('phancong-form').addEventListener('submit', function(e) {
        const selectedTopics = document.querySelectorAll('.topic-checkbox:checked');
        
        if (selectedTopics.length === 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất 1 nhóm!');
            return false;
        }
        
        const magvPhanbien = document.getElementById('magv_phanbien').value;
        if (!magvPhanbien) {
            e.preventDefault();
            alert('Vui lòng chọn giảng viên phản biện!');
            return false;
        }
        
        let hasError = false;
        selectedTopics.forEach(chk => {
            const magvHd = chk.getAttribute('data-magv-hd');
            if (magvHd === magvPhanbien) {
                alert('Cảnh báo: Giảng viên hướng dẫn không được làm phản biện!');
                hasError = true;
            }
        });
        
        if (hasError) {
            e.preventDefault();
            return false;
        }
        
        const confirmed = confirm(`Bạn có chắc muốn phân công ${selectedTopics.length} nhóm cho giảng viên này?`);
        if (!confirmed) {
            e.preventDefault();
            return false;
        }
    });
});

// ✅ CẬP NHẬT SỐ LƯỢNG ĐÃ CHỌN
function updateSelectedCount() {
    const count = document.querySelectorAll('.topic-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

// ✅ XUẤT EXCEL
function exportExcel(e) {
    e.preventDefault();
    
    const selectedTopics = document.querySelectorAll('.topic-checkbox:checked');
    
    if (selectedTopics.length === 0) {
        alert('Vui lòng chọn ít nhất 1 nhóm để xuất!');
        return false;
    }
    
    const selectedIds = Array.from(selectedTopics).map(chk => chk.value).join(',');
    
    // Lấy danh sách cột đang visible
    const visibleColumns = [];
    const columnNames = ['nhom', 'mssv', 'tensv', 'detai', 'gvhd', 'gvphanbien'];
    
    document.querySelectorAll('.col-toggle').forEach((checkbox, idx) => {
        if (checkbox.checked) {
            visibleColumns.push(columnNames[idx]);
        }
    });
    
    console.log('Visible columns:', visibleColumns);
    
    let url = `{{ route('admin.phanbien.export') }}?nhom_ids=${selectedIds}&visible_columns=${visibleColumns.join(',')}`;
    
    console.log('Export URL:', url);
    window.location.href = url;
}
</script>

@endsection