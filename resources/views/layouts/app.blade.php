<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản lý Luận Văn')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar {
            width: 250px;
            background: #fff;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1px solid #e0e0e0;
            overflow-y: auto;
        }
        .sidebar a {
            display: block;
            padding: 10px 20px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #e8f0fe;
            color: #0d6efd;
        }
        .content {
            margin-left: 250px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        footer {
            margin-top: auto;
            background: #fff;
            text-align: center;
            padding: 10px;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<body>

    <!-- Sidebar -->
<div class="sidebar">
    <div class="text-center py-3 border-bottom">
        <h5 class="fw-bold mb-0">QL Luận Văn</h5>
    </div>
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="fa fa-home me-2"></i> Dashboard
    </a>
    <a href="{{ route('timeline.index') }}" class="{{ request()->routeIs('timeline.index') ? 'active' : '' }}">
        <i class="fa fa-clock me-2"></i> Các Mốc Thời Gian
    </a>

    <!-- Dropdown Danh Sách SV -->
    <a class="d-flex justify-content-between align-items-center" 
       data-bs-toggle="collapse" href="#svMenu" role="button" aria-expanded="false" aria-controls="svMenu">
        <span><i class="fa fa-users me-2"></i> Sinh Viên</span>
        <i class="fa fa-chevron-down small"></i>
    </a>
    <div class="collapse ps-4" id="svMenu">
        <a href="{{ route('students.create') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('students.create') ? 'text-primary fw-bold' : '' }}">
            ➕ Thêm sinh viên
        </a>
        <a href="{{ route('students.edit.list') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('students.edit.list') ? 'text-primary fw-bold' : '' }}">
            ✏️ Sửa sinh viên
        </a>
        <a href="{{ route('students.index') }}" class="d-block py-1 text-decoration-none text-secondary {{ request()->routeIs('students.index') ? 'text-primary fw-bold' : '' }}">
            📋 Danh sách sinh viên
        </a>
    </div>

    <a href="{{ route('lecturers.index') }}" class="{{ request()->routeIs('lecturers.index') ? 'active' : '' }}">
        <i class="fa fa-chalkboard-teacher me-2"></i> Giảng Viên
    </a>

    <a href="{{ route('assignments.index') }}" class="{{ request()->routeIs('assignments.index') ? 'active' : '' }}">
        <i class="fa fa-tasks me-2"></i> Phân Công Hướng Dẫn
    </a>

    <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.index') ? 'active' : '' }}">
        <i class="fa fa-cog me-2"></i> Cài Đặt
    </a>
</div>

    <!-- Main content -->
    <div class="content">
        <header class="bg-white border-bottom p-3 d-flex justify-content-between align-items-center shadow-sm">
            <h4 class="mb-0">@yield('header', 'Trang chủ')</h4>
            <div class="d-flex align-items-center gap-3">
                <i class="fa fa-bell text-secondary"></i>
                <div class="d-flex align-items-center">
                    <img src="https://i.pravatar.cc/40" class="rounded-circle me-2" alt="">
                    <span>Staff</span>
                </div>
            </div>
        </header>

        <main class="flex-fill p-4">
            @yield('content')
        </main>

        <footer>
            © 2025 Khoa CNTT - Quản lý Luận Văn
        </footer>
    </div>

</body>
</html>
