@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Danh sách đề tài được giảng viên gửi</h4>

    <form method="GET" action="{{ route('admin.topics') }}" class="mb-3 d-flex align-items-center gap-2">
        <label for="lecturer" class="fw-bold mb-0">Lọc theo giảng viên:</label>
        <select name="lecturer" id="lecturer" class="form-select" style="width: 250px;" onchange="this.form.submit()">
            <option value="">-- Tất cả giảng viên --</option>
            @foreach ($lecturers as $gv)
                <option value="{{ $gv->tengv }}" {{ request('lecturer') == $gv->tengv ? 'selected' : '' }}>
                    {{ $gv->tengv }}
                </option>
            @endforeach
        </select>
    </form>

    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>MSSV</th>
                <th>Tên đề tài</th>
                <th>Giảng viên</th>
                <th>Ngày gửi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topics as $topic)
                <tr>
                    <td>{{ $topic->mssv }}</td>
                    <td>{{ $topic->tendt }}</td>
                    <td>{{ $topic->tengv }}</td>
                    <td>{{ \Carbon\Carbon::parse($topic->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Chưa có đề tài nào được gửi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
