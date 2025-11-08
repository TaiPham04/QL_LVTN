@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Phân công giảng viên cho sinh viên</h2>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('assignments.bulkSave') }}" method="POST">
        @csrf
        <div class="d-flex align-items-center mb-3">
            <label for="magv" class="me-2 fw-bold">Chọn giảng viên:</label>
            <select name="magv" id="magv" class="form-control w-auto me-3">
                <option value="">-- Chọn giảng viên --</option>
                @foreach($lecturers as $lecturer)
                    <option value="{{ $lecturer->magv }}">{{ $lecturer->hoten }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-success">Lưu phân công</button>
        </div>

        @if($students->isEmpty())
            <div class="alert alert-info">🎉 Tất cả sinh viên đã được phân công!</div>
        @else
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>
                            <input type="checkbox" id="checkAll"> <!-- check all -->
                        </th>
                        <th>MSSV</th>
                        <th>Họ tên</th>
                        <th>Lớp</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                    <tr>
                        <td><input type="checkbox" name="students[]" value="{{ $student->mssv }}" class="student-checkbox"></td>
                        <td>{{ $student->mssv }}</td>
                        <td>{{ $student->hoten }}</td>
                        <td>{{ $student->lop }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </form>
</div>

<script>
    // check all / uncheck all
    document.getElementById('checkAll').addEventListener('change', function(e) {
        document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = e.target.checked);
    });
</script>
@endsection
