<ul class="sidebar-menu">
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="fa fa-home me-2"></i> Trang chủ
    </a>

    <!-- Danh sách đề tài -->
    <a href="{{ route('admin.topics.index') }}" 
    class="{{ request()->routeIs('admin.topics.index') ? 'active' : '' }}">
        <i class="fa fa-book me-2"></i> Danh sách đề tài
    </a>

    <!-- Sinh Viên -->
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

    <!-- Giảng Viên -->
    <a class="d-flex justify-content-between align-items-center" 
        data-bs-toggle="collapse" href="#gvMenu" role="button" aria-expanded="false" aria-controls="gvMenu">
        <span><i class="fa fa-chalkboard-teacher me-2"></i> Giảng Viên</span>
        <i class="fa fa-chevron-down small"></i>
    </a>
    <div class="collapse ps-4" id="gvMenu">
        <a href="{{ route('admin.lecturers.create') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('admin.lecturers.create') ? 'text-primary fw-bold' : '' }}">Thêm giảng viên</a>
        <a href="{{ route('admin.lecturers.edit.list') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('admin.lecturers.edit.list') ? 'text-primary fw-bold' : '' }}">Sửa giảng viên</a>
        <a href="{{ route('admin.lecturers.index') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('admin.lecturers.index') ? 'text-primary fw-bold' : '' }}">Danh sách giảng viên</a>
    </div>

    <!-- Phân Công -->
    <a class="d-flex justify-content-between align-items-center" 
        data-bs-toggle="collapse" href="#assignMenu" role="button" aria-expanded="false" aria-controls="assignMenu">
        <span><i class="fa fa-tasks me-2"></i> Phân Công</span>
        <i class="fa fa-chevron-down small"></i>
    </a>
    <div class="collapse ps-4" id="assignMenu">
        <a href="{{ route('admin.assignments.form') }}" 
        class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('admin.assignments.form') ? 'text-primary fw-bold' : '' }}">
        Phân Giảng Viên
        </a>

        <a href="{{ route('admin.assignments.index') }}" 
        class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('admin.assignments.index') ? 'text-primary fw-bold' : '' }}">
        Bảng Phân Công
        </a>

        <a href="{{ route('admin.phanbien.index') }}" 
        class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('admin.phanbien.index') ? 'text-primary fw-bold' : '' }}">
        Phân Công Phản Biện
        </a>

        <a href="{{ route('admin.hoidong.index') }}" 
        class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('admin.hoidong.*') ? 'text-primary fw-bold' : '' }}">
        Quản Lý Hội Đồng
        </a>
    </div>

    <a href="{{ route('admin.diem.index') }}" class="{{ request()->routeIs('admin.diem.*') ? 'active' : '' }}">
        <i class="fa fa-chart-bar me-2"></i> Bảng Điểm
    </a>

    <!-- Đăng xuất -->
    <a href="#" onclick="globalLogout(event); return false;" 
       class="d-block py-1 text-decoration-none text-danger">
        <i class="fa fa-sign-out-alt me-2"></i> Đăng xuất
    </a>
</ul>