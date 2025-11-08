@extends('layouts.app')

@section('header', 'Phân công đề tài')

@section('content')
<div class="container">
    <h4 class="mb-3">Phân công đề tài</h4>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('lecturers.assignments.store') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-success mb-3">Lưu phân công</button>

        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th></th>
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
                        <td>
                            <input type="checkbox" name="students[]" value="{{ $sv->mssv }}">
                        </td>
                        <td>{{ $sv->mssv }}</td>
                        <td>{{ $sv->hoten }}</td>
                        <td>
                            {{ $sv->nhom ?? 'Chưa có' }}
                        </td>
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
</div>
@endsection
