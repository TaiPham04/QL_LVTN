@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fa fa-chart-line me-2"></i>Điểm Tổng Kết
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

            {{-- Alert Container for Export --}}
            <div id="alert-container"></div>

            {{-- Danh Sách Điểm --}}
            @if($khongCoDiem)
                <div class="text-center py-5">
                    <i class="fa fa-chart-line fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không có dữ liệu điểm tổng kết</h5>
                    <p class="text-muted">Bạn chưa có trong hội đồng nào hoặc chưa có sinh viên</p>
                </div>
            @else
                @foreach($danhSachTongKet as $item)
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                <strong>{{ $item['hoiDong']->mahd }}</strong> - {{ $item['hoiDong']->tenhd }}
                            </h6>
                            
                            {{-- Nút Xuất Excel (Chỉ Chủ Tịch & Thư Ký) --}}
                            @if(in_array($item['hoiDong']->vai_tro, ['chu_tich', 'thu_ky']))
                                <button type="button" 
                                        class="btn btn-success btn-sm export-excel-btn"
                                        data-mahd="{{ $item['hoiDong']->mahd }}"
                                        title="Xuất Excel">
                                    <i class="fa fa-file-excel me-1"></i>Xuất Excel
                                </button>
                            @endif
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 8%">MSSV</th>
                                        <th style="width: 15%">Tên Sinh Viên</th>
                                        <th style="width: 8%">Nhóm</th>
                                        <th style="width: 8%">Lớp</th>
                                        <th style="width: 15%">GVHD</th>
                                        <th style="width: 20%">Tên Đề Tài</th>
                                        <th style="width: 8%" class="text-center">Điểm HD</th>
                                        <th style="width: 8%" class="text-center">Điểm PB</th>
                                        <th style="width: 10%" class="text-center">Điểm Hội Đồng</th>
                                        <th style="width: 12%" class="text-center">Điểm Tổng Kết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($item['sinhVienDiem'] as $sv)
                                        <tr>
                                            <td>{{ $sv['mssv'] }}</td>
                                            <td>{{ $sv['hoten'] }}</td>
                                            <td class="text-center">{{ $sv['nhom'] }}</td>
                                            <td class="text-center">{{ $sv['lop'] }}</td>
                                            <td>{{ $sv['gvhd'] ?? 'N/A' }}</td>
                                            <td>{{ $sv['tendt'] }}</td>
                                            <td class="text-center">
                                                {{ $sv['diem_hd'] !== '' ? number_format($sv['diem_hd'], 2) : '-' }}
                                            </td>
                                            <td class="text-center">
                                                {{ $sv['diem_pb'] !== '' ? number_format($sv['diem_pb'], 2) : '-' }}
                                            </td>
                                            <td class="text-center">
                                                {{ $sv['diem_hoidong'] !== '' ? number_format($sv['diem_hoidong'], 2) : '-' }}
                                            </td>
                                            <td class="text-center">
                                                @if($sv['diem_tongket'] !== '')
                                                    <strong>{{ number_format($sv['diem_tongket'], 2) }}</strong>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-3">
                                                Không có sinh viên
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
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

    .alert {
        border-radius: 8px;
        border: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportBtns = document.querySelectorAll('.export-excel-btn');
    
    exportBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const mahd = this.dataset.mahd;
            // Dùng route helper thay vì URL cứng
            const url = "{{ route('lecturers.tong-ket.export-excel', ':mahd') }}".replace(':mahd', mahd);
            
            console.log('Exporting:', mahd, 'URL:', url);
            
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
                        showAlert('warning', data.error || 'Lỗi không xác định');
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
                a.download = `DiemTongKet_${mahd}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(blobUrl);
                a.remove();
                
                showAlert('success', 'Xuất file Excel thành công!');
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
    
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        
        let icon = 'check-circle';
        if (type === 'warning') icon = 'exclamation-triangle';
        else if (type === 'danger') icon = 'exclamation-circle';
        
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