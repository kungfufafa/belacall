<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BELACALL - Layanan Aspirasi Desa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('home') }}" class="text-2xl font-bold text-green-700">BELACALL</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('report.create') }}" class="text-gray-600 hover:text-green-700 px-3 py-2 rounded-md text-sm font-medium">Buat Laporan</a>
                    <a href="{{ route('report.tracking.view') }}" class="text-gray-600 hover:text-green-700 px-3 py-2 rounded-md text-sm font-medium">Cek Status</a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                &copy; {{ date('Y') }} BELACALL - Pemerintah Desa. All rights reserved.
            </p>
        </div>
    </footer>
</body>
</html>
