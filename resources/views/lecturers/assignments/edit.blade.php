@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Admin không có chức năng edit phân công. Vui lòng xóa và phân công lại.
    </div>
    
    <a href="{{ route('admin.assignments.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
    </a>
</div>
@endsection