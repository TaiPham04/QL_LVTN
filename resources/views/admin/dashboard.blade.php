@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-3">Trang chủ Admin</h2>
    <p>Xin chào, {{ session('user')->name ?? 'Admin' }} 👋</p>
    <p>Chọn một chức năng trong menu để bắt đầu làm việc.</p>
</div>
@endsection