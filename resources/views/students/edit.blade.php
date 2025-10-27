@extends('layouts.app')

@section('header', 'Sửa sinh viên')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Danh sách sinh viên - Chỉnh sửa</h5>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>MSSV</th>
                <th>Họ tên</th>
                <th>Lớp</th>
                <th>Email</th>
                <th>SĐT</th>
                <th class="text-center" style="width:120px;">Thao tác</th> 
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
                    <td class="text-center">
                        <a href="{{ route('students.edit.form', $sv->mssv) }}" class="btn btn-sm btn-warning">
                            <i class="fa fa-edit"></i> Sửa
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection