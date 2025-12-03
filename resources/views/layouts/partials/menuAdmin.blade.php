<ul class="sidebar-menu">
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="fa fa-home me-2"></i> Dashboard
    </a>

    <!-- Các Mốc Thời Gian -->
    <a class="d-flex justify-content-between align-items-center" 
        data-bs-toggle="collapse" href="#timelineMenu" role="button" aria-expanded="false" aria-controls="timelineMenu">
        <span><i class="fa fa-clock me-2"></i> Các Mốc Thời Gian</span>
        <i class="fa fa-chevron-down small"></i>
    </a>
    <div class="collapse ps-4" id="timelineMenu">
        <a href="{{ route('timeline.index') }}" 
        class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('timeline.index') ? 'text-primary fw-bold' : '' }}">
            Quản lý mốc thời gian
        </a>
        <a href="{{ route('admin.topics.index') }}" 
        class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('admin.topics.index') ? 'text-primary fw-bold' : '' }}">
            Danh sách đề tài
        </a>
    </div>

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
        <a href="{{ route('lecturers.create') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('lecturers.create') ? 'text-primary fw-bold' : '' }}">Thêm giảng viên</a>
        <a href="{{ route('lecturers.edit.list') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('lecturers.edit.list') ? 'text-primary fw-bold' : '' }}">Sửa giảng viên</a>
        <a href="{{ route('lecturers.index') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('lecturers.index') ? 'text-primary fw-bold' : '' }}">Danh sách giảng viên</a>
    </div>

    <!-- Phân Công -->
    <a class="d-flex justify-content-between align-items-center" 
        data-bs-toggle="collapse" href="#assignMenu" role="button" aria-expanded="false" aria-controls="assignMenu">
        <span><i class="fa fa-tasks me-2"></i> Phân Công</span>
        <i class="fa fa-chevron-down small"></i>
    </a>
    <div class="collapse ps-4" id="assignMenu">
        <a href="{{ route('assignments.form') }}" 
        class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('assignments.form') ? 'text-primary fw-bold' : '' }}">
        Phân Giảng Viên
        </a>

        <a href="{{ route('assignments.index') }}" 
        class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('assignments.index') ? 'text-primary fw-bold' : '' }}">
        Bảng Phân Công
        </a>

        <a href="{{ route('admin.phanbien.index') }}" 
        class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('admin.phanbien.index') ? 'text-primary fw-bold' : '' }}">
        Phân Công Phản Biện
        </a>
    </div>

    <!-- Cài Đặt -->
    <a class="d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#settingsMenu" role="button" aria-expanded="false" aria-controls="settingsMenu">
        <span><i class="fa fa-cog me-2"></i> Cài Đặt</span>
        <i class="fa fa-chevron-down small"></i>
    </a>
    <div class="collapse ps-4" id="settingsMenu">
        <a href="{{ route('settings.index') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('settings.index') ? 'text-primary fw-bold' : '' }}">Cấu hình hệ thống</a>
        
        <!-- Đăng xuất - SỬ DỤNG HÀM globalLogout() -->
        <a href="#" onclick="globalLogout(event); return false;" 
           class="d-block py-1 text-decoration-none text-danger">
            <i class="fa fa-sign-out-alt me-2"></i> Đăng xuất
        </a>
    </div>
</ul>