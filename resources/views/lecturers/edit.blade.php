@extends('layouts.app')

@section('header', 'Sửa giảng viên')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Danh sách giảng viên - Chỉnh sửa</h5>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Mã GV</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th class="text-center" style="width:120px;">Thao tác</th> 
            </tr>
        </thead>
        <tbody>
            @foreach ($lecturers as $gv)
                <tr>
                    <td>{{ $gv->magv }}</td>
                    <td>{{ $gv->hoten }}</td>
                    <td>{{ $gv->email }}</td>
                    <td class="text-center">
                        <a href="{{ route('lecturers.edit', $gv->magv) }}" class="btn btn-sm btn-warning">
                            <i class="fa fa-edit"></i> Sửa
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
