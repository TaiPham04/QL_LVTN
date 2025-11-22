@extends('layouts.app')

@section('header', 'Phân công đề tài')

@section('content')
<div class="container">
    <h4 class="mb-3">Phân công đề tài</h4>

    {{-- Hiển thị thông báo --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Hàng nút điều khiển --}}
    <div class="d-flex justify-content-start gap-2 mb-3">
        <button form="assignmentForm" type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> Lưu phân công
        </button>
        <button type="button" class="btn btn-danger" id="deleteButton">
            <i class="bi bi-trash"></i> Xóa thông tin
        </button>
    </div>

    {{-- Form lưu phân công (chứa toàn bộ bảng) --}}
    <form action="{{ route('lecturers.assignments.store') }}" method="POST" id="assignmentForm">
        @csrf
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px; text-align:center;">
                        <input type="checkbox" id="selectAll">
                    </th>
                    <th>MSSV</th>
                    <th>Họ tên</th>
                    <th>Nhóm</th>
                    <th>Tên đề tài</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($students as $sv)
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" name="students[]" value="{{ $sv->mssv }}" class="student-checkbox">
                        </td>
                        <td>{{ $sv->mssv }}</td>
                        <td>{{ $sv->hoten }}</td>
                        <td>{{ $sv->nhom ?? 'Chưa có' }}</td>
                        <td>
                            <input type="text" name="titles[{{ $sv->mssv }}]" class="form-control"
                                   value="{{ $sv->tendt ?? '' }}" placeholder="Nhập tên đề tài">
                        </td>
                        <td>
                            <input type="text" name="statuses[{{ $sv->mssv }}]" class="form-control"
                                   value="{{ $sv->trangthai ?? '' }}" placeholder="VD: Đang thực hiện">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </form>

    {{-- Form ẩn để XÓA --}}
    <form id="deleteForm" action="{{ route('lecturers.assignments.delete') }}" method="POST" style="display:none;">
        @csrf
        <input type="hidden" name="students" id="deleteStudents">
    </form>
</div>

{{-- JS xử lý nút XÓA và "Chọn tất cả" --}}
<script>
    // Xử lý checkbox "Chọn tất cả"
    document.getElementById('selectAll').addEventListener('change', function () {
        const checked = this.checked;
        document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = checked);
    });

    // Xử lý nút XÓA
    document.getElementById('deleteButton').addEventListener('click', function () {
        const selected = Array.from(document.querySelectorAll('.student-checkbox:checked'))
            .map(cb => cb.value);

        if (selected.length === 0) {
            alert('⚠️ Vui lòng chọn ít nhất 1 sinh viên để xóa!');
            return;
        }

        if (!confirm('Bạn có chắc chắn muốn xóa thông tin các sinh viên được chọn không?')) {
            return;
        }

        document.getElementById('deleteStudents').value = JSON.stringify(selected);
        document.getElementById('deleteForm').submit();
    });
</script>
@endsection