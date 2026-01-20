@extends('layouts.app')

@section('content')
<div class="bg-green-700 pb-32">
    <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl md:text-6xl">
            Sampaikan Masalah,<br>Kami Tuntaskan.
        </h1>
        <p class="mt-4 max-w-2xl mx-auto text-xl text-green-100">
            Layanan pengaduan masyarakat desa yang cepat, transparan, dan responsif. Laporkan jalan rusak, sampah, atau layanan publik lainnya.
        </p>
        <div class="mt-10 flex justify-center gap-4">
            <a href="{{ route('report.create') }}" class="px-8 py-3 border border-transparent text-base font-medium rounded-md text-green-700 bg-white hover:bg-green-50 md:text-lg md:px-10 shadow-lg">
                Lapor Sekarang
            </a>
            <a href="{{ route('report.tracking.view') }}" class="px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-800 hover:bg-green-900 md:text-lg md:px-10">
                Lacak Laporan
            </a>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-24">
    <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
        <!-- Card 1 -->
        <div class="bg-white rounded-lg shadow-lg p-6 text-center">
            <div class="mx-auto h-12 w-12 text-green-600 text-4xl mb-4">📸</div>
            <h3 class="text-lg font-medium text-gray-900">1. Foto & Lapor</h3>
            <p class="mt-2 text-base text-gray-500">
                Ambil foto masalah di sekitar Anda, isi formulir singkat, dan kirim laporan.
            </p>
        </div>
        <!-- Card 2 -->
        <div class="bg-white rounded-lg shadow-lg p-6 text-center">
            <div class="mx-auto h-12 w-12 text-green-600 text-4xl mb-4">⚡</div>
            <h3 class="text-lg font-medium text-gray-900">2. Verifikasi Cepat</h3>
            <p class="mt-2 text-base text-gray-500">
                Laporan diverifikasi oleh operator desa dan diteruskan ke petugas terkait.
            </p>
        </div>
        <!-- Card 3 -->
        <div class="bg-white rounded-lg shadow-lg p-6 text-center">
            <div class="mx-auto h-12 w-12 text-green-600 text-4xl mb-4">✅</div>
            <h3 class="text-lg font-medium text-gray-900">3. Selesai</h3>
            <p class="mt-2 text-base text-gray-500">
                Pantau progress hingga masalah selesai ditangani. Anda akan dapat notifikasi.
            </p>
        </div>
    </div>
</div>
    <div class="mt-16 bg-green-50 rounded-xl p-8 border border-green-100">
        <div class="flex flex-col md:flex-row items-center justify-between">
            <div class="text-left mb-6 md:mb-0">
                <h2 class="text-2xl font-bold text-green-900">Lebih Suka Chatting?</h2>
                <p class="mt-2 text-green-700">
                    Anda juga bisa melaporkan masalah langsung melalui WhatsApp Bot kami.<br>
                    Cukup ketik <strong>"LAPOR"</strong> dan ikuti petunjuk selanjutnya.
                </p>
            </div>
            <a href="https://wa.me/6281234567890?text=LAPOR" target="_blank" class="flex items-center px-6 py-3 bg-green-600 text-white font-bold rounded-full hover:bg-green-700 transition shadow-lg">
                <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                Chat WhatsApp
            </a>
        </div>
    </div>
</div>
@endsection
