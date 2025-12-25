@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fa fa-chart-line me-2"></i>Tổng Kết - Danh Sách Hội Đồng
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
            @if($hoiDongList->isEmpty())
                <div class="text-center py-5">
                    <i class="fa fa-chart-line fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không có hội đồng nào</h5>
                    <p class="text-muted">Bạn không phải là Chủ tịch hoặc Thư ký của hội đồng nào</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light" style="background-color: #f8f9fa;">
                            <tr>
                                <th class="text-center" style="width: 5%;">STT</th>
                                <th style="width: 10%;">Mã Hội Đồng</th>
                                <th style="width: 25%;">Tên Hội Đồng</th>
                                <th class="text-center" style="width: 12%;">Ngày Hội Đồng</th>
                                <th class="text-center" style="width: 12%;">Vai Trò</th>
                                <th class="text-center" style="width: 10%;">Đề Tài</th>
                                <th class="text-center" style="width: 12%;">Trạng Thái</th>
                                <th class="text-center" style="width: 14%;">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hoiDongList as $index => $hd)
                                <tr>
                                    <td class="text-center"><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <span class="badge bg-primary">{{ $hd->mahd }}</span>
                                    </td>
                                    <td>{{ $hd->tenhd }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fa fa-calendar me-1"></i>{{ \Carbon\Carbon::parse($hd->ngay_hoidong)->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($hd->vai_tro === 'chu_tich')
                                            <span class="badge bg-warning text-dark">Chủ tịch</span>
                                        @elseif($hd->vai_tro === 'thu_ky')
                                            <span class="badge bg-info">Thư ký</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $hd->vai_tro }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($hd->so_de_tai > 0)
                                            <span class="badge bg-success">{{ $hd->so_de_tai }} đề tài</span>
                                        @else
                                            <span class="badge bg-secondary">0 đề tài</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($hd->trang_thai === 'dang_mo')
                                            <span class="badge bg-success">Đang mở</span>
                                        @elseif($hd->trang_thai === 'da_dong')
                                            <span class="badge bg-danger">Đã đóng</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $hd->trang_thai }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('lecturers.tong-ket.show', $hd->hoidong_id) }}" 
                                           class="btn btn-sm btn-info" 
                                           title="Xem chi tiết">
                                            <i class="fa fa-eye"></i> Xem
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-success export-excel-btn"
                                                data-hoidong-id="{{ $hd->hoidong_id }}"
                                                title="Xuất Excel">
                                            <i class="fa fa-file-excel"></i> Excel
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
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
    .table {
        border: 1px solid #dee2e6;
    }

    .table th {
        font-weight: 600;
        border: 1px solid #dee2e6;
        border-top: none;
        padding: 12px;
    }

    .table td {
        vertical-align: middle;
        padding: 10px;
        border: 1px solid #dee2e6;
    }

    .table thead th {
        border-bottom: 2px solid #dee2e6;
    }

    .table tbody tr:last-child td {
        border-bottom: 1px solid #dee2e6;
    }

    .alert {
        border-radius: 8px;
        border: none;
    }

    .badge {
        font-size: 11px;
        padding: 5px 8px;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 13px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportBtns = document.querySelectorAll('.export-excel-btn');
    
    exportBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const hoiDongId = this.getAttribute('data-hoidong-id');
            const originalHtml = this.innerHTML;
            
            this.disabled = true;
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            
            const url = `{{ route('lecturers.tong-ket.export-excel') }}?hoidong_id=${hoiDongId}`;
            
            console.log('Exporting hoidong_id:', hoiDongId);
            
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
                a.download = `DiemTongKet_${hoiDongId}_${new Date().toLocaleDateString('vi-VN')}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(blobUrl);
                a.remove();
                
                showAlert('success', '✓ Xuất file Excel thành công!');
                
                this.disabled = false;
                this.innerHTML = originalHtml;
            })
            .catch(error => {
                console.error('Error:', error);
                this.disabled = false;
                this.innerHTML = originalHtml;
            });
        });
    });
    
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
        
        const pageBody = document.querySelector('.card-body');
        if (pageBody) {
            pageBody.insertBefore(alertDiv, pageBody.firstChild);
        }
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});
</script>

@endsection