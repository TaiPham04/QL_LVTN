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
<body>
    @php
        $user = session('user');
    @endphp

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center py-3 border-bottom">
            <h5 class="fw-bold mb-0">QL Luận Văn</h5>
        </div>

        @php $role = session('user')->role ?? null; @endphp

        @switch($role)
            @case('admin')
                @include('layouts.partials.menuAdmin')
                @break

            @case('giangvien')
                @include('layouts.partials.menuLecturers')
                @break

            @default
                <p class="text-center mt-3 text-secondary">Không có quyền truy cập</p>
        @endswitch
    </div>

    <!-- Main content -->
    <div class="content">
        <header class="bg-white border-bottom p-3 d-flex justify-content-between align-items-center shadow-sm">
            <h4 class="mb-0">@yield('header', 'Trang chủ')</h4>
            <div class="d-flex align-items-center gap-3">
                <!-- Thông báo -->
                <div class="dropdown me-3">
                    <a href="#" class="text-decoration-none text-dark position-relative" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-bell fs-5"></i>
                        @if(session('notifications') && count(session('notifications')) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ count(session('notifications')) }}
                            </span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="dropdown-header fw-bold">Thông báo</li>
                        @forelse (session('notifications', []) as $notify)
                            <li><span class="dropdown-item small">{{ $notify }}</span></li>
                        @empty
                            <li><span class="dropdown-item small text-muted">Không có thông báo mới</span></li>
                        @endforelse
                    </ul>
                </div>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none text-dark" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://i.pravatar.cc/40" class="rounded-circle me-2" alt="">
                        <span class="fw-semibold">{{ $user ? ucfirst($user->role) : 'Khách' }}</span>
                    </a>
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

    <!-- Form đăng xuất chung cho toàn bộ app -->
    <form id="global-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hàm đăng xuất toàn cục
        function globalLogout(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                document.getElementById('global-logout-form').submit();
            }
            return false;
        }

        // Bỏ qua tất cả validation cho logout
        document.addEventListener('DOMContentLoaded', function() {
            const logoutForm = document.getElementById('global-logout-form');
            if (logoutForm) {
                logoutForm.addEventListener('submit', function(e) {
                    e.stopImmediatePropagation();
                });
            }
        });
    </script>
</body>
</html>