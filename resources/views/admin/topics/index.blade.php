@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Danh sách đề tài</h4>

    {{-- Form lọc --}}
    <form method="GET" action="{{ route('admin.topics.index') }}" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="lecturer" class="form-label fw-bold">Lọc theo giảng viên:</label>
                <select name="lecturer" id="lecturer" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả giảng viên --</option>
                    @foreach ($lecturers as $gv)
                        <option value="{{ $gv->tengv }}" {{ request('lecturer') == $gv->tengv ? 'selected' : '' }}>
                            {{ $gv->tengv }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label fw-bold">Trạng thái đề tài:</label>
                <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả --</option>
                    <option value="co_detai" {{ request('status') == 'co_detai' ? 'selected' : '' }}>✅ Có đề tài</option>
                    <option value="chua_detai" {{ request('status') == 'chua_detai' ? 'selected' : '' }}>❌ Chưa có đề tài</option>
                </select>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;" class="text-center">Nhóm</th>
                    <th style="width: 120px;">MSSV</th>
                    <th style="width: 220px;">Tên sinh viên</th>
                    <th>Tên đề tài</th>
                    <th style="width: 180px;">Giảng viên HD</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groupedTopics as $group)
                    @php $studentCount = count($group['students']); @endphp
                    @foreach ($group['students'] as $index => $student)
                        <tr>
                            @if($index === 0)
                                {{-- Cột Nhóm - chỉ hiển thị 1 lần --}}
                                <td rowspan="{{ $studentCount }}" class="text-center align-middle">
                                    @if($group['nhom'])
                                        <span class="badge bg-secondary fs-6">{{ $group['nhom'] }}</span>
                                    @else
                                        <span class="text-muted">Chưa có</span>
                                    @endif
                                </td>
                            @endif
                            
                            {{-- MSSV và Tên sinh viên - hiển thị mỗi dòng --}}
                            <td>{{ $student['mssv'] }}</td>
                            <td>{{ $student['tensv'] }}</td>
                            
                            @if($index === 0)
                                {{-- Tên đề tài và GVHD - chỉ hiển thị 1 lần --}}
                                <td rowspan="{{ $studentCount }}" class="align-middle">
                                    @if($group['tendt'])
                                        {{ $group['tendt'] }}
                                    @else
                                        <span class="text-danger fst-italic">Chưa có đề tài</span>
                                    @endif
                                </td>
                                <td rowspan="{{ $studentCount }}" class="align-middle">
                                    <small class="text-primary">
                                        <i class="bi bi-person-badge"></i> {{ $group['tengv'] }}
                                    </small>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Không tìm thấy dữ liệu phù hợp.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(count($groupedTopics) > 0)
        <div class="mt-3 text-muted">
            <i class="bi bi-info-circle"></i> 
            Tổng: <strong>{{ count($groupedTopics) }}</strong> nhóm
            ({{ collect($groupedTopics)->sum(fn($g) => count($g['students'])) }} sinh viên)
        </div>
    @endif
</div>
@endsection