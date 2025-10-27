@extends('layouts.app')

@section('header', 'Danh sách sinh viên')

@section('content')
<div class="container"> 
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Danh sách sinh viên</h5>
        <div>
            <a href="" class="btn btn-success">Xuất file excel</a>
            <a href="{{ route('students.create') }}" class="btn btn-primary">+ Thêm sinh viên</a>
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
                <th>Email</th>
                <th>SĐT</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $sv)
                <tr>
                    <td>{{ $sv->mssv }}</td>
                    <td>{{ $sv->hoten }}</td>
                    <td>{{ $sv->lop }}</td>
                    <td>{{ $sv->email }}</td>
                    <td>{{ $sv->sdt }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection