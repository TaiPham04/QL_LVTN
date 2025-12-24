@extends('layouts.app')
@section('header', 'Bảng Điểm Tổng Kết')
@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📊 Bảng Điểm Tổng Kết</h5>
                </div>

                {{-- Filters --}}
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <form method="GET" class="row g-3 w-100">
                            <div class="col-md-4">
                                <label class="form-label">Hội Đồng</label>
                                <select name="hoidong_id" class="form-control" id="hoidong_select" onchange="document.querySelector('form').submit();">
                                    <option value="">-- Tất cả --</option>
                                    @foreach ($hoiDongs as $hd)
                                        <option value="{{ $hd->mahd }}" 
                                            @if(request('hoidong_id') == $hd->mahd) selected @endif>
                                            {{ $hd->tenhd }} ({{ date('d/m/Y', strtotime($hd->ngay_hoidong)) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Nhóm/Đề Tài</label>
                                <input type="text" name="nhom_id" class="form-control" placeholder="Mã nhóm" value="{{ request('nhom_id') }}">
                            </div>

                            <div class="col-md-4 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Lọc
                                </button>
                                <a href="{{ route('admin.diem.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Làm mới
                                </a>
                                @if ($diem->count() > 0)
                                    <button type="button" class="btn btn-success" id="export-excel-btn">
                                        <i class="fas fa-download"></i> Xuất Excel
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Bảng điểm --}}
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 8%">Nhóm</th>
                                <th style="width: 12%">Tên Hội Đồng</th>
                                <th style="width: 20%">Tên Đề Tài</th>
                                <th style="width: 10%">MSSV</th>
                                <th style="width: 18%">Tên SV</th>
                                <th style="width: 8%">Lớp</th>
                                <th style="width: 8%">Điểm HD</th>
                                <th style="width: 8%">Điểm PB</th>
                                <th style="width: 8%">Điểm HĐ</th>
                                <th style="width: 10%">Tổng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($diem as $item)
                                <tr>
                                    <td><span class="badge bg-info">{{ $item['tennhom'] }}</span></td>
                                    <td><strong>{{ $item['tenhd'] }}</strong></td>
                                    <td>{{ $item['tendt'] }}</td>
                                    <td><code>{{ $item['mssv'] }}</code></td>
                                    <td>{{ $item['ten_sinh_vien'] }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $item['lop'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($item['diem_hd'] > 0)
                                            <span class="badge bg-info">{{ number_format($item['diem_hd'], 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item['diem_pb'] > 0)
                                            <span class="badge bg-info">{{ number_format($item['diem_pb'], 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item['diem_gv'] > 0)
                                            <span class="badge bg-warning">{{ number_format($item['diem_gv'], 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <strong>
                                            @if ($item['diem_tong'] > 0)
                                                <span class="badge bg-success">{{ number_format($item['diem_tong'], 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        📭 Không có dữ liệu
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer text-muted">
                    <small>📌 Tổng số bản ghi: <strong>{{ $diem->count() }}</strong></small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    code {
        background-color: #f5f5f5;
        padding: 2px 6px;
        border-radius: 3px;
        color: #333;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportBtn = document.getElementById('export-excel-btn');
    
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Lấy query params hiện tại
            const url = "{{ route('admin.diem.export') }}" + window.location.search;
            
            console.log('Exporting to:', url);
            window.location.href = url;
        });
    }
});
</script>

@endsection