<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-100 via-blue-200 to-blue-300 min-h-screen flex items-center justify-center">

    <div class="bg-white shadow-2xl rounded-2xl w-full max-w-md p-8">
        <div class="text-center mb-6">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" 
                 alt="Logo" class="w-20 h-20 mx-auto mb-3">
            <h1 class="text-2xl font-bold text-gray-700">HỆ THỐNG QUẢN LÝ ĐỒ ÁN</h1>
            <p class="text-sm text-gray-500 mt-1">Đăng nhập để tiếp tục</p>
        </div>

        {{-- Hiển thị thông báo lỗi --}}
        @if(session('error'))
            <div class="bg-red-100 text-red-700 border border-red-300 rounded-md px-4 py-2 mb-4 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="block text-gray-600 font-semibold mb-1">Tên đăng nhập</label>
                <input type="text" name="username" id="username"
                    value="{{ old('username') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 outline-none"
                    placeholder="Nhập tên đăng nhập">
                @error('username')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-gray-600 font-semibold mb-1">Mật khẩu</label>
                <input type="password" name="password" id="password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 outline-none"
                    placeholder="••••••••">
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between mt-4">
                <label class="flex items-center text-sm text-gray-600">
                    <input type="checkbox" class="mr-2 rounded" name="remember">
                    Ghi nhớ đăng nhập
                </label>
                <a href="#" class="text-sm text-blue-600 hover:underline">Quên mật khẩu?</a>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2.5 mt-4 rounded-lg hover:bg-blue-700 transition-all duration-200">
                Đăng nhập
            </button>
        </form>

        <p class="text-center text-gray-500 text-sm mt-6">
            &copy; {{ date('Y') }} Hệ thống quản lý đồ án tốt nghiệp
        </p>
    </div>

</body>
</html>
