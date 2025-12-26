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

        <div class="row">
            {{-- Bảng danh sách nhóm --}}
            <div class="col-md-8">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-folder2-open me-2"></i>Danh sách nhóm đề tài</span>
                        <span class="badge bg-light text-dark">Tổng: {{ count($groupedTopics) }} nhóm</span>
                    </div>

                    {{-- ✅ DataTable Controls Container --}}
                    <div class="card-body border-bottom p-3" id="table-controls" style="background: #f8f9fa; display: flex; justify-content: space-between; align-items: center; gap: 15px;"></div>

                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
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
            </div>

            {{-- Form chọn giảng viên phản biện --}}
            <div class="col-md-4">
                <div class="card shadow-sm rounded-3 sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-person-check-fill me-2"></i>Chọn giảng viên phản biện
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Hướng dẫn:</strong><br>
                            1. Chọn các nhóm bên trái<br>
                            2. Chọn 1 giảng viên phản biện<br>
                            3. Nhấn "Lưu phân công"
                        </div>

                        <div class="mb-3">
                            <label for="magv_phanbien" class="form-label fw-bold">
                                <i class="bi bi-person-circle text-primary"></i> Giảng viên phản biện
                                <span class="text-danger">*</span>
                            </label>
                            <select name="magv_phanbien" id="magv_phanbien" class="form-select form-select-lg" required>
                                <option value="">-- Chọn giảng viên --</option>
                                @foreach ($giangviens as $gv)
                                <option value="{{ $gv->magv }}">{{ $gv->hoten }}</option>
                                @endforeach
                            </select>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>Lưu phân công
                            </button>
                            <a href="#" onclick="exportExcel(event)" class="btn btn-success btn-lg">
                                <i class="bi bi-file-earmark-excel me-2"></i>Xuất Excel
                            </a>
                        </div>

                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="mb-2">
                                <i class="bi bi-check-square text-success"></i>
                                <strong>Đã chọn:</strong> <span id="selectedCount">0</span> nhóm
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-lightbulb"></i> 
                                Có thể chọn nhiều nhóm để phân công cùng lúc
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ✅ DataTables CSS + JS --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

<style>
    /* Style toolbar */
    #table-controls {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 15px !important;
        flex-wrap: wrap !important;
    }

    #table-controls .dt-buttons {
        display: inline-flex !important;
        gap: 10px !important;
        align-items: center !important;
        order: 1;
    }

    #table-controls .dt-buttons button {
        border-radius: 6px !important;
        padding: 6px 14px !important;
        font-weight: 500 !important;
        white-space: nowrap !important;
    }

    #table-controls .dataTables_filter {
        display: inline-flex !important;
        align-items: center !important;
        gap: 10px !important;
        margin: 0 !important;
        order: 2;
    }

    #table-controls .dataTables_filter label {
        display: inline-block !important;
        margin: 0 !important;
        white-space: nowrap !important;
        font-weight: 500 !important;
        font-size: 14px !important;
    }

    #table-controls .dataTables_filter input {
        border-radius: 6px !important;
        padding: 8px 12px !important;
        border: 1px solid #dee2e6 !important;
        width: 250px !important;
    }

    #table-controls .dataTables_filter input:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25) !important;
    }

    .table thead {
        background-color: #f8f9fa !important;
    }

    .table tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05) !important;
    }
</style>

<script>
$(document).ready(function() {
    // ✅ Destroy DataTable cũ nếu có (để reload lại)
    if ($.fn.DataTable.isDataTable('#phanBienTable')) {
        $('#phanBienTable').DataTable().destroy();
    }

    // ✅ Khởi tạo DataTable với Column Visibility + Smart Search
    var table = $('#phanBienTable').DataTable({
        dom: 'B<"clear">frtip',
        buttons: [
            {
                extend: 'colvis',
                text: '<i class="bi bi-eye-fill"></i> Hiển thị/Ẩn cột',
                className: 'btn btn-sm btn-outline-primary',
                columns: ':not(.never)'
            }
        ],
        columnDefs: [
            {
                targets: 0,  // Checkbox column
                visible: true,
                searchable: false,
                className: 'never'
            }
        ],
        paging: false,
        searching: true,
        ordering: true,
        info: false,
        language: {
            search: "Tìm kiếm:",
            searchPlaceholder: "Tìm theo: nhóm, MSSV, đề tài, giảng viên...",
            zeroRecords: "Không tìm thấy dữ liệu",
            infoEmpty: "Không có dữ liệu"
        },
        initComplete: function() {
            // ✅ Di chuyển buttons vào table-controls
            $('div.dt-buttons').prependTo('#table-controls');
            
            // ✅ Di chuyển search vào table-controls
            $('div.dataTables_filter').appendTo('#table-controls');
            
            // Style input
            $('div.dataTables_filter input')
                .addClass('form-control form-control-sm')
                .attr('placeholder', 'Tìm theo: nhóm, MSSV, đề tài, giảng viên...');
        }
    });

    // ✅ Custom search filter dựa trên column visibility
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var searchValue = $('div.dataTables_filter input').val().toLowerCase();
        
        if (!searchValue) {
            return true; // Không tìm → show all
        }
        
        // Lấy visibility của cột GVHD (5) và GV Phản biện (6)
        var gvhdVisible = table.column(5).visible();
        var gvpbVisible = table.column(6).visible();
        
        // Lấy dữ liệu từ data array (text của cell)
        var gvhdData = data[5] ? data[5].toLowerCase() : '';
        var gvpbData = data[6] ? data[6].toLowerCase() : '';
        
        // Nếu cột GVHD visible → tìm trong GVHD
        if (gvhdVisible && gvhdData.includes(searchValue)) {
            return true;
        }
        
        // Nếu cột GV Phản biện visible → tìm trong GV Phản biện
        if (gvpbVisible && gvpbData.includes(searchValue)) {
            return true;
        }
        
        // Tìm trong các cột khác (Nhóm, MSSV, Tên, Đề tài)
        var nhomData = data[1] ? data[1].toLowerCase() : '';
        var mssvData = data[2] ? data[2].toLowerCase() : '';
        var tensvData = data[3] ? data[3].toLowerCase() : '';
        var dtData = data[4] ? data[4].toLowerCase() : '';
        
        if (nhomData.includes(searchValue) || mssvData.includes(searchValue) || tensvData.includes(searchValue) || dtData.includes(searchValue)) {
            return true;
        }
        
        return false;
    });

    // ✅ Thêm event listener khi change column visibility
    table.on('column-visibility.dt', function(e, settings, column, state) {
        console.log('Column ' + column + ' is now ' + (state ? 'visible' : 'hidden'));
        
        // Trigger search redraw để apply custom filter
        var currentSearch = table.search();
        table.search(currentSearch).draw();
    });
});
</script>

<script>
// ✅ Lưu và khôi phục state khi tìm kiếm

// Khôi phục giảng viên phản biện từ sessionStorage
window.addEventListener('load', function() {
    const savedGV = sessionStorage.getItem('selected_gv');
    if (savedGV) {
        document.getElementById('magv_phanbien').value = savedGV;
    }
    
    // Khôi phục checkbox đã chọn
    const savedCheckboxes = JSON.parse(sessionStorage.getItem('selected_topics') || '[]');
    const currentCheckboxes = Array.from(document.querySelectorAll('.topic-checkbox')).map(chk => chk.value);
    
    savedCheckboxes.forEach(nhomId => {
        if (currentCheckboxes.includes(nhomId)) {
            const checkbox = document.querySelector(`input[value="${nhomId}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        }
    });
    
    updateSelectedCount();
});

// Lưu giảng viên khi thay đổi
document.getElementById('magv_phanbien').addEventListener('change', function() {
    sessionStorage.setItem('selected_gv', this.value);
});

// Lưu checkbox khi thay đổi
document.querySelectorAll('.topic-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const selectedIds = Array.from(document.querySelectorAll('.topic-checkbox:checked'))
            .map(chk => chk.value);
        sessionStorage.setItem('selected_topics', JSON.stringify(selectedIds));
        updateSelectedCount();
    });
});

// Chọn tất cả checkbox
document.getElementById('checkAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.topic-checkbox');
    checkboxes.forEach(chk => chk.checked = this.checked);
    
    const selectedIds = Array.from(document.querySelectorAll('.topic-checkbox:checked'))
        .map(chk => chk.value);
    sessionStorage.setItem('selected_topics', JSON.stringify(selectedIds));
    updateSelectedCount();
});

// Cập nhật số lượng đã chọn
function updateSelectedCount() {
    const count = document.querySelectorAll('.topic-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

// ✅ Xuất Excel - CHỈ các cột hiển thị
function exportExcel(e) {
    e.preventDefault();
    
    const selectedTopics = document.querySelectorAll('.topic-checkbox:checked');
    
    if (selectedTopics.length === 0) {
        alert('Vui lòng chọn ít nhất 1 nhóm để xuất!');
        return false;
    }
    
    // Lấy danh sách nhóm được chọn
    const selectedIds = Array.from(selectedTopics).map(chk => chk.value).join(',');
    
    // ✅ Lấy danh sách cột HIỂN THỊ
    const table = $('#phanBienTable').DataTable();
    const visibleColumns = [];
    
    // Danh sách tất cả các cột (theo thứ tự trong table) - BỎ CHECKBOX
    const columnNames = ['nhom', 'mssv', 'tensv', 'detai', 'gvhd', 'gvphanbien'];    
    
    // Kiểm tra từng cột xem visible hay không (bắt đầu từ index 1 vì bỏ checkbox ở index 0)
    columnNames.forEach((colName, idx) => {
        const colIndex = idx + 1; // +1 vì checkbox là cột 0
        
        if (table.column(colIndex).visible()) {
            visibleColumns.push(colName);
        }
    });
    
    console.log('Visible columns:', visibleColumns);
    
    // Redirect tới route export với parameters
    let url = `{{ route('admin.phanbien.export') }}?nhom_ids=${selectedIds}&visible_columns=${visibleColumns.join(',')}`;
    
    console.log('Export URL:', url);
    window.location.href = url;
}

// Kiểm tra trước khi submit
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
    
    // Kiểm tra GVHD không được làm phản biện
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

updateSelectedCount();
</script>

@endsection