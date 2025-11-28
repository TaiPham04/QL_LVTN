<ul class="sidebar-menu">
    <a href="{{ route('lecturers.home') }}" 
       class="d-block py-2 text-decoration-none text-secondary {{ request()->routeIs('lecturers.home') ? 'text-primary fw-bold' : '' }}">
        <i class="fa fa-home me-2"></i> Trang chủ
    </a>

    <a href="{{ route('lecturers.students.index') }}" 
       class="d-block py-2 text-decoration-none text-secondary {{ request()->routeIs('lecturers.students.index') ? 'text-primary fw-bold' : '' }}">
        <i class="fa fa-users me-2"></i> Danh sách sinh viên
    </a>

    <a href="{{ route('lecturers.assignments.form') }}" 
       class="d-block py-2 text-decoration-none text-secondary {{ request()->routeIs('lecturers.assignments.form') ? 'text-primary fw-bold' : '' }}">
        <i class="fa fa-tasks me-2"></i> Nhóm & Đề tài
    </a>

    <a href="{{ route('lecturers.diemgiuaky.index') }}" 
       class="d-block py-2 text-decoration-none text-secondary {{ request()->routeIs('lecturers.diemgiuaky.index') ? 'text-primary fw-bold' : '' }}">
        <i class="fa fa-clipboard-check me-2"></i> Điểm giữa kỳ
    </a>

    <hr class="my-2">

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-link text-danger text-decoration-none p-0 w-100 text-start ps-2">
            <i class="fa fa-sign-out-alt me-2"></i> Đăng xuất
        </button>
    </form>
</ul>