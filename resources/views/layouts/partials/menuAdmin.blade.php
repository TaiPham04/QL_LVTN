<ul class="sidebar-menu">
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="fa fa-home me-2"></i> Dashboard
    </a>
    <a href="{{ route('timeline.index') }}" class="{{ request()->routeIs('timeline.index') ? 'active' : '' }}">
        <i class="fa fa-clock me-2"></i> Các Mốc Thời Gian
    </a>

    <a class="d-flex justify-content-between align-items-center" 
        data-bs-toggle="collapse" href="#svMenu" role="button" aria-expanded="false" aria-controls="svMenu">
        <span><i class="fa fa-users me-2"></i> Sinh Viên</span>
        <i class="fa fa-chevron-down small"></i>
    </a>
    <div class="collapse ps-4" id="svMenu">
        <a href="{{ route('students.create') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('students.create') ? 'text-primary fw-bold' : '' }}">Thêm sinh viên</a>
        <a href="{{ route('students.edit.list') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('students.edit.list') ? 'text-primary fw-bold' : '' }}">Sửa sinh viên</a>
        <a href="{{ route('students.index') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('students.index') ? 'text-primary fw-bold' : '' }}">Danh sách sinh viên</a>
    </div>

    <a class="d-flex justify-content-between align-items-center" 
        data-bs-toggle="collapse" href="#gvMenu" role="button" aria-expanded="false" aria-controls="gvMenu">
        <span><i class="fa fa-chalkboard-teacher me-2"></i> Giảng Viên</span>
        <i class="fa fa-chevron-down small"></i>
    </a>
    <div class="collapse ps-4" id="gvMenu">
        <a href="{{ route('lecturers.create') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('lecturers.create') ? 'text-primary fw-bold' : '' }}">Thêm giảng viên</a>
        <a href="{{ route('lecturers.edit.list') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('lecturers.edit.list') ? 'text-primary fw-bold' : '' }}">Sửa giảng viên</a>
        <a href="{{ route('lecturers.index') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('lecturers.index') ? 'text-primary fw-bold' : '' }}">Danh sách giảng viên</a>
    </div>

    <a class="d-flex justify-content-between align-items-center" 
        data-bs-toggle="collapse" href="#assignMenu" role="button" aria-expanded="false" aria-controls="assignMenu">
        <span><i class="fa fa-tasks me-2"></i> Phân Công</span>
        <i class="fa fa-chevron-down small"></i>
    </a>
    <div class="collapse ps-4" id="assignMenu">
        <a href="{{ route('assignments.form') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('assignments.form') ? 'text-primary fw-bold' : '' }}">Phân Giảng Viên</a>
        <a href="{{ route('assignments.index') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('assignments.index') ? 'text-primary fw-bold' : '' }}">Bảng Phân Công</a>
    </div>

    {{-- Menu cài đặt --}}
    <a class="d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#settingsMenu" role="button" aria-expanded="false" aria-controls="settingsMenu">
        <span><i class="fa fa-cog me-2"></i> Cài Đặt</span>
        <i class="fa fa-chevron-down small"></i>
    </a>
    <div class="collapse ps-4" id="settingsMenu">
        <a href="{{ route('settings.index') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('settings.index') ? 'text-primary fw-bold' : '' }}">Cấu hình hệ thống</a>
        <form action="{{ route('logout') }}" method="POST" class="d-block py-1">
            @csrf
            <button type="submit" class="btn btn-link text-danger text-decoration-none p-0">Đăng xuất</button>
        </form>
    </div>
</ul>
