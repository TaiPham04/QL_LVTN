<ul class="sidebar-menu list-unstyled">
    <!-- Trang chủ -->
    <li>
        <a href="{{ route('lecturers.home') }}" 
           class="d-block py-2 px-3 text-decoration-none text-secondary rounded {{ request()->routeIs('lecturers.home') ? 'text-primary fw-bold bg-light' : '' }}">
            <i class="fa fa-home me-2"></i> Trang chủ
        </a>
    </li>

    <!-- Danh sách sinh viên -->
    <li>
        <a href="{{ route('lecturers.students.index') }}" 
           class="d-block py-2 px-3 text-decoration-none text-secondary rounded {{ request()->routeIs('lecturers.students.index') ? 'text-primary fw-bold bg-light' : '' }}">
            <i class="fa fa-users me-2"></i> Danh sách sinh viên
        </a>
    </li>

    <!-- Nhóm & Đề tài -->
    <li>
        <a href="{{ route('lecturers.assignments.form') }}" 
           class="d-block py-2 px-3 text-decoration-none text-secondary rounded {{ request()->routeIs('lecturers.assignments.form') ? 'text-primary fw-bold bg-light' : '' }}">
            <i class="fa fa-tasks me-2"></i> Nhóm & Đề tài
        </a>
    </li>

    <!-- Điểm giữa kỳ -->
    <li>
        <a href="{{ route('lecturers.diemgiuaky.index') }}" 
           class="d-block py-2 px-3 text-decoration-none text-secondary rounded {{ request()->routeIs('lecturers.diemgiuaky.index') ? 'text-primary fw-bold bg-light' : '' }}">
            <i class="fa fa-clipboard-check me-2"></i> Điểm giữa kỳ
        </a>
    </li>

    <!-- Divider -->
    <li><hr class="my-2"></li>

    <!-- Menu Chấm Điểm (Collapsible) -->
    <li>
        <a class="d-flex justify-content-between align-items-center py-2 px-3 text-decoration-none text-secondary rounded" 
           data-bs-toggle="collapse" href="#chamDiemMenu" role="button" aria-expanded="false" aria-controls="chamDiemMenu">
            <span><i class="fa fa-graduation-cap me-2"></i> Chấm Điểm</span>
            <i class="fa fa-chevron-down small"></i>
        </a>
        <div class="collapse ps-4" id="chamDiemMenu">
            <a href="{{ route('lecturers.chamdiem.huongdan.index') }}" 
               class="d-block py-2 px-2 text-decoration-none text-secondary rounded {{ request()->routeIs('lecturers.chamdiem.huongdan.*') ? 'text-primary fw-bold' : '' }}">
                <i class="fa fa-clipboard-check me-2"></i> Hướng dẫn
            </a>
            <a href="{{ route('lecturers.chamdiem.phanbien.index') }}" 
               class="d-block py-2 px-2 text-decoration-none text-secondary rounded {{ request()->routeIs('lecturers.chamdiem.phanbien.*') ? 'text-primary fw-bold' : '' }}">
                <i class="fa fa-user-check me-2"></i> Phản biện
            </a>
        </div>
    </li>

    <!-- Divider -->
    <li><hr class="my-2"></li>

    <!-- Đăng xuất -->
    <li>
        <a href="#" onclick="globalLogout(event); return false;" 
           class="d-block py-2 px-3 text-decoration-none text-danger rounded">
            <i class="fa fa-sign-out-alt me-2"></i> Đăng xuất
        </a>
    </li>
</ul>