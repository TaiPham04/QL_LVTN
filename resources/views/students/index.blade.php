@extends('layouts.app')

@section('header', 'Danh sách sinh viên')

@section('content')
<div class="container"> 
    <div class="d-flex justify-content-between mb-3">
        <h4>Danh sách sinh viên</h4>
        <div>
            <a href="{{ route('students.export') }}" class="btn btn-success" download>
                <i class="fa fa-file-excel me-1"></i> Xuất file excel
            </a>

            {{-- Chỉ hiển thị nút thêm sinh viên nếu là admin --}}
            @if(session('user') && session('user')->role === 'admin')
                <a href="{{ route('students.create') }}" class="btn btn-primary">+ Thêm sinh viên</a>
            @endif
        </div>
    </div>


    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>MSSV</th>
                <th>Họ tên</th>
                <th>Lớp</th>
                <th>SĐT</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $sv)
                <tr>
                    <td>{{ $sv->mssv }}</td>
                    <td>{{ $sv->hoten }}</td>
                    <td>{{ $sv->lop }}</td>
                    <td>{{ $sv->sdt }}</td>
                    <td>{{ $sv->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection