@extends('layouts.app')

@section('header', 'Điểm giữa kỳ')

@section('content')
<div class="container-fluid">
    <h4 class="mb-3">Chấm điểm và đánh giá giữa kỳ</h4>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-start gap-2 mb-3">
        <button form="diemForm" type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> Lưu đánh giá
        </button>
    </div>

    <form action="{{ route('lecturers.diemgiuaky.store') }}" method="POST" id="diemForm">
        @csrf
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;" class="text-center">Nhóm</th>
                        <th style="width: 120px;">MSSV</th>
                        <th style="width: 180px;">Tên sinh viên</th>
                        <th style="width: 250px;">Đề tài</th>
                        <th style="width: 100px;">Điểm</th>
                        <th style="width: 180px;">Kết quả đánh giá</th>
                        <th style="width: 250px;">Nhận xét</th>
                        <th style="width: 130px;" class="text-center">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groupedStudents as $group)
                        @php $studentCount = count($group['students']); @endphp
                        @foreach ($group['students'] as $index => $sv)
                            <tr>
                                @if($index === 0)
                                    {{-- Cột Nhóm - CHỈ gộp cột này thôi --}}
                                    <td rowspan="{{ $studentCount }}" class="text-center align-middle">
                                        @if($group['nhom'] && $group['nhom'] !== 'Chưa có')
                                            <span class="badge bg-secondary fs-6">{{ $group['nhom'] }}</span>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </td>
                                @endif
                                
                                {{-- Các cột còn lại hiển thị từng dòng --}}
                                <td>
                                    {{ $sv['mssv'] }}
                                    <input type="hidden" name="mssv[]" value="{{ $sv['mssv'] }}">
                                </td>
                                <td>{{ $sv['tensv'] }}</td>
                                <td>
                                    @if($group['tendt'])
                                        {{ $group['tendt'] }}
                                    @else
                                        <span class="text-danger fst-italic">Chưa có đề tài</span>
                                    @endif
                                </td>
                                <td>
                                    <input type="number" 
                                           name="diem[{{ $sv['mssv'] }}]" 
                                           class="form-control" 
                                           value="{{ $sv['diem'] }}"
                                           min="0" 
                                           max="10" 
                                           step="0.25"
                                           placeholder="0-10">
                                </td>
                                <td>
                                    <select name="ketqua[{{ $sv['mssv'] }}]" class="form-select">
                                        <option value="chua_danh_gia" {{ $sv['ketqua'] == 'chua_danh_gia' || !$sv['ketqua'] ? 'selected' : '' }}>
                                            Chưa đánh giá
                                        </option>
                                        <option value="duoc_tieptuc" {{ $sv['ketqua'] == 'duoc_tieptuc' ? 'selected' : '' }}>
                                            ✅ Được tiếp tục
                                        </option>
                                        <option value="khong_duoc_tieptuc" {{ $sv['ketqua'] == 'khong_duoc_tieptuc' ? 'selected' : '' }}>
                                            ❌ Không được tiếp tục
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <textarea name="nhanxet[{{ $sv['mssv'] }}]" 
                                              class="form-control" 
                                              rows="2" 
                                              placeholder="Nhận xét...">{{ $sv['nhanxet'] }}</textarea>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $sv['badge_class'] }}">
                                        {{ $sv['trangthai'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Không có sinh viên nào được phân công
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if(count($groupedStudents) > 0)
        <div class="mt-3 alert alert-info">
            <strong><i class="bi bi-info-circle me-2"></i>Hướng dẫn:</strong><br>
            <div class="mt-2">
                <span class="badge bg-secondary">Chưa đánh giá</span> - Chưa chấm điểm và đánh giá<br>
                <span class="badge bg-success">Được tiếp tục</span> - Sinh viên được phép tiếp tục làm luận văn<br>
                <span class="badge bg-danger">Không được tiếp tục</span> - Sinh viên phải dừng lại hoặc làm lại<br>
            </div>
            <div class="mt-2">
                • Điểm từ 0 đến 10, có thể nhập số thập phân (VD: 7.5)<br>
                • Nhận xét giúp sinh viên hiểu rõ hơn về kết quả
            </div>
        </div>
    @endif
</div>
@endsection