@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            {{-- HEADER --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-1">
                        <i class="fa fa-clipboard-list me-2 text-primary"></i>Nhiệm Vụ Bài Thi Tốt Nghiệp
                    </h3>
                    <p class="text-muted mb-0">Nhóm: <strong>{{ $group->tennhom }}</strong> - <strong>{{ $group->tendt }}</strong></p>
                </div>
                <a href="{{ route('lecturers.nhiemvu.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- THÔNG TIN CỐ ĐỊNH --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fa fa-university me-2 text-muted"></i>Thông Tin Cố Định</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Trường:</strong> Đại học Công nghệ Sài Gòn
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Khoa:</strong> Công Nghệ Thông Tin
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Ngành:</strong> Tin học
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Trưởng Khoa:</strong> (Ký và ghi rõ họ tên)
                        </div>
                    </div>
                </div>
            </div>

            {{-- FORM --}}
            <form method="POST" action="{{ route('lecturers.nhiemvu.store') }}">
                @csrf
                <input type="hidden" name="nhom_id" value="{{ $nhom_id }}">

                {{-- THÔNG TIN SINH VIÊN --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fa fa-users me-2"></i>Thông Tin Sinh Viên</h5>
                    </div>
                    <div class="card-body">
                        @if($students->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%">STT</th>
                                            <th style="width: 40%">Họ và Tên</th>
                                            <th style="width: 30%">MSSV</th>
                                            <th style="width: 25%">Lớp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($students as $index => $student)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td><strong>{{ $student->hoten }}</strong></td>
                                                <td>{{ $student->mssv }}</td>
                                                <td>{{ $student->lop }}</td>
                                            </tr>
                                            
                                            {{-- HIDDEN INPUTS ĐỂ GỬI DỮ LIỆU --}}
                                            @if($index == 0)
                                                <input type="hidden" name="sv1_hoten" value="{{ $student->hoten }}">
                                                <input type="hidden" name="sv1_mssv" value="{{ $student->mssv }}">
                                                <input type="hidden" name="sv1_lop" value="{{ $student->lop }}">
                                            @elseif($index == 1)
                                                <input type="hidden" name="sv2_hoten" value="{{ $student->hoten }}">
                                                <input type="hidden" name="sv2_mssv" value="{{ $student->mssv }}">
                                                <input type="hidden" name="sv2_lop" value="{{ $student->lop }}">
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">
                                <i class="fa fa-info-circle me-1"></i>
                                Thông tin sinh viên được lấy tự động từ nhóm đã tạo
                            </small>
                        @else
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                Không tìm thấy sinh viên trong nhóm này!
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 1. ĐẦU ĐỀ BÀI THI --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">1. Đầu đề bài thi <span class="text-warning">*</span></h5>
                    </div>
                    <div class="card-body">
                        <textarea name="dau_de_bai_thi" class="form-control" rows="3" 
                                  placeholder="Ví dụ: Xây dựng website bán hoa tươi" required>{{ old('dau_de_bai_thi', $nhiemvu->dau_de_bai_thi ?? $group->tendt) }}</textarea>
                    </div>
                </div>

                {{-- 2. NHIỆM VỤ YÊU CẦU --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">2. Nhiệm vụ yêu cầu về nội dung và số liệu ban đầu <span class="text-warning">*</span></h5>
                    </div>
                    <div class="card-body">
                        <textarea name="nhiem_vu_noi_dung" class="form-control" rows="6" 
                                  placeholder="Ví dụ:&#10;- Tìm hiểu nghiệp vụ quản lý, bán hàng của một cửa hàng bán hoa tươi.&#10;- Tìm hiểu các công nghệ phát triển website.&#10;- Xây dựng CSDL&#10;- Cài đặt ứng dụng web bằng công nghệ: React, Node.js và Mysql" 
                                  required>{{ old('nhiem_vu_noi_dung', $nhiemvu->nhiem_vu_noi_dung ?? '') }}</textarea>
                        <small class="text-muted">
                            <i class="fa fa-lightbulb me-1"></i>
                            Mỗi nhiệm vụ nên xuống dòng để dễ đọc
                        </small>
                    </div>
                </div>

                {{-- 3. HỒ SƠ TÀI LIỆU --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">3. Các hồ sơ và tài liệu cung cấp ban đầu</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="ho_so_tai_lieu" class="form-control" rows="3" 
                                  placeholder="Nếu không có thì để trống">{{ old('ho_so_tai_lieu', $nhiemvu->ho_so_tai_lieu ?? '') }}</textarea>
                        <small class="text-muted">
                            <i class="fa fa-info-circle me-1"></i>
                            Có thể để trống nếu không có tài liệu cung cấp
                        </small>
                    </div>
                </div>

                {{-- 4 & 5. THỜI GIAN --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fa fa-calendar me-2"></i>Thời Gian Thực Hiện</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    4. Ngày giao nhiệm vụ bài thi <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="ngay_giao" class="form-control" 
                                       value="{{ old('ngay_giao', $nhiemvu->ngay_giao ?? date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    5. Ngày hoàn thành nhiệm vụ <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="ngay_hoanthanh" class="form-control" 
                                       value="{{ old('ngay_hoanthanh', $nhiemvu->ngay_hoanthanh ?? '') }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 6. NGƯỜI HƯỚNG DẪN --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">6. Họ tên người hướng dẫn</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    1. Người hướng dẫn <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="nguoi_huongdan_1" class="form-control" 
                                       value="{{ old('nguoi_huongdan_1', $nhiemvu->nguoi_huongdan_1 ?? $lecturer->hoten) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Phần hướng dẫn <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="phan_huongdan_1" class="form-control" 
                                       value="{{ old('phan_huongdan_1', $nhiemvu->phan_huongdan_1 ?? 'Toàn bộ') }}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">2. Người hướng dẫn (nếu có)</label>
                                <input type="text" name="nguoi_huongdan_2" class="form-control" 
                                       value="{{ old('nguoi_huongdan_2', $nhiemvu->nguoi_huongdan_2 ?? '') }}"
                                       placeholder="Để trống nếu không có người hướng dẫn thứ 2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phần hướng dẫn</label>
                                <input type="text" name="phan_huongdan_2" class="form-control" 
                                       value="{{ old('phan_huongdan_2', $nhiemvu->phan_huongdan_2 ?? '') }}"
                                       placeholder="Để trống nếu không có">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BUTTONS --}}
                <div class="d-flex gap-2 justify-content-end mb-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa fa-save me-2"></i>Lưu Nhiệm Vụ
                    </button>
                    <a href="{{ route('lecturers.nhiemvu.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fa fa-times me-2"></i>Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 12px;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    padding: 15px 20px;
}

.form-label {
    margin-bottom: 8px;
    color: #495057;
}

.form-control, .form-select {
    border-radius: 8px;
    padding: 10px 14px;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.btn-lg {
    padding: 12px 24px;
    font-weight: 500;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}
</style>
@endsection