<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Terkirim - BELACALL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 flex items-center justify-center min-h-screen p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden text-center p-8">
        <div class="mb-6">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
                <i class="fas fa-check text-4xl text-green-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Diterima!</h1>
            <p class="text-gray-500 mt-2">Terima kasih sudah peduli dengan lingkungan kita.</p>
        </div>

        <div class="bg-gray-50 rounded-xl p-6 mb-8 border border-gray-100">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Nomor Tiket Anda</p>
            <p class="text-3xl font-mono font-bold text-blue-600 select-all">{{ $report->ticket_number }}</p>
        </div>

        <div class="space-y-3">
            <a href="{{ route('reports.create') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition">
                Buat Laporan Lain
            </a>
            <a href="/" class="block w-full bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold py-3 px-4 rounded-xl transition">
                Kembali ke Beranda
            </a>
        </div>
        
        <p class="mt-8 text-xs text-gray-400">
            Kami juga mengirimkan detail laporan ini ke WhatsApp Anda: <br>
            <strong>{{ $report->user->phone }}</strong>
        </p>
    </div>

</body>
</html>
