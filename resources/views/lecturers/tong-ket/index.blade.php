@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fa fa-chart-line me-2"></i>Điểm Tổng Kết - Các Đề Tài Hướng Dẫn
            </h5>
            @if(!$khongCoDiem)
                <button type="button" 
                        class="btn btn-success btn-sm export-excel-btn"
                        title="Xuất Excel">
                    <i class="fa fa-file-excel me-2"></i>Xuất Excel
                </button>
            @endif
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

            {{-- Alert Container for Export --}}
            <div id="alert-container"></div>

            {{-- Danh Sách Điểm --}}
            @if($khongCoDiem)
                <div class="text-center py-5">
                    <i class="fa fa-chart-line fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không có dữ liệu điểm tổng kết</h5>
                    <p class="text-muted">Bạn chưa hướng dẫn đề tài nào hoặc chưa có điểm</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center" style="width: 7%">MSSV</th>
                                <th style="width: 15%">Tên Sinh Viên</th>
                                <th class="text-center" style="width: 6%">Nhóm</th>
                                <th class="text-center" style="width: 6%">Lớp</th>
                                <th style="width: 25%">Tên Đề Tài</th>
                                <th class="text-center" style="width: 8%">Điểm HD</th>
                                <th class="text-center" style="width: 8%">Điểm PB</th>
                                <th class="text-center" style="width: 10%">Điểm HĐ</th>
                                <th class="text-center" style="width: 10%">Điểm Tổng Kết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($danhSachTongKet as $sv)
                                <tr>
                                    <td class="text-center"><code>{{ $sv['mssv'] }}</code></td>
                                    <td>{{ $sv['hoten'] }}</td>
                                    <td class="text-center">{{ $sv['nhom'] }}</td>
                                    <td class="text-center">{{ $sv['lop'] }}</td>
                                    <td>{{ $sv['tendt'] }}</td>
                                    <td class="text-center">
                                        @if($sv['diem_hd'] !== '')
                                            <span class="badge bg-info">{{ number_format($sv['diem_hd'], 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($sv['diem_pb'] !== '')
                                            <span class="badge bg-info">{{ number_format($sv['diem_pb'], 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($sv['diem_hoidong'] !== '')
                                            <span class="badge bg-warning">{{ number_format($sv['diem_hoidong'], 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($sv['diem_tongket'] !== '')
                                            <strong><span class="badge bg-success">{{ number_format($sv['diem_tongket'], 2) }}</span></strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                        Không có dữ liệu
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
        border-top: none;
        padding: 12px;
    }

    .table td {
        vertical-align: middle;
        padding: 10px;
    }

    .alert {
        border-radius: 8px;
        border: none;
    }

    code {
        background-color: #f5f5f5;
        padding: 2px 6px;
        border-radius: 3px;
        color: #333;
    }

    .badge {
        font-size: 12px;
        padding: 5px 8px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportBtn = document.querySelector('.export-excel-btn');
    
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            this.disabled = true;
            this.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Đang xuất...';
            
            const url = "{{ route('lecturers.tong-ket.export-excel') }}";
            
            console.log('Exporting all data, URL:', url);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    return response.json().then(data => {
                        showAlert('danger', data.error || 'Lỗi không xác định');
                        throw new Error(data.error);
                    });
                }
                
                return response.blob();
            })
            .then(blob => {
                console.log('Blob received:', blob.size);
                
                const blobUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = `DiemTongKet_${new Date().toLocaleDateString('vi-VN')}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(blobUrl);
                a.remove();
                
                showAlert('success', '✓ Xuất file Excel thành công!');
                
                exportBtn.disabled = false;
                exportBtn.innerHTML = '<i class="fa fa-file-excel me-2"></i>Xuất Excel';
            })
            .catch(error => {
                console.error('Error:', error);
                exportBtn.disabled = false;
                exportBtn.innerHTML = '<i class="fa fa-file-excel me-2"></i>Xuất Excel';
            });
        });
    }
    
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        
        let icon = 'check-circle';
        if (type === 'danger') icon = 'exclamation-circle';
        if (type === 'warning') icon = 'exclamation-triangle';
        
        alertDiv.innerHTML = `
            <i class="fa fa-${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.getElementById('alert-container');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
        }
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});
</script>

@endsection