<ul class="sidebar-menu">
    <a href="{{ route('lecturers.home') }}" class="text-decoration-none text-secondary">
        <span><i class="fa fa-home me-2"></i> Trang chủ</span>
    </a>

    <a href="{{ route('lecturers.students.index') }}" class="text-decoration-none text-secondary">
        <span><i class="fa fa-users me-2"></i> Danh sách sinh viên</span>
    </a>

    <a href="{{ route('lecturers.assignments.form') }}" class="text-decoration-none text-secondary">
        <span><i class="fa fa-tasks me-2"></i> Nhóm & Đề tài</span>
    </a>

    <form action="{{ route('logout') }}" method="POST" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-link text-danger text-decoration-none p-0 ms-2">
            <i class="fa fa-sign-out-alt me-2"></i> Đăng xuất
        </button>
    </form>
</ul>
