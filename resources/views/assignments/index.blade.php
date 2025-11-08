@extends('layouts.app')

@section('title', 'Bảng Phân Công')
@section('header', 'Bảng Phân Công Hướng Dẫn')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('assignments.index') }}" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="magv" class="col-form-label fw-semibold">Chọn giảng viên:</label>
                </div>
                <div class="col-auto">
                    <select name="magv" id="magv" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Tất cả --</option>
                        @foreach($lecturers as $gv)
                            <option value="{{ $gv->magv }}" {{ $selectedLecturer == $gv->magv ? 'selected' : '' }}>
                                {{ $gv->hoten }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            <table class="table table-striped mt-4 align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>MSSV</th>
                        <th>Họ tên sinh viên</th>
                        <th>Lớp</th>
                        <th>Giảng viên hướng dẫn</th>
                        <th>Thời gian phân công</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $a)
                        <tr>
                            <td>{{ $a->student->mssv }}</td>
                            <td>{{ $a->student->hoten }}</td>
                            <td>{{ $a->student->lop }}</td>
                            <td>{{ $a->lecturer->hoten }}</td>
                            <td>{{ $a->tg_phancong }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Không có dữ liệu phân công</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
