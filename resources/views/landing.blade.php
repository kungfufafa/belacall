@extends('layouts.app')
@use('Illuminate\Support\Facades\Storage')

@section('content')
<div class="relative isolate overflow-hidden bg-white -mt-24 pt-24 -mb-12">
    <!-- Hero Section -->
    <div class="px-6 py-24 sm:px-6 sm:py-32 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
                Sampaikan Masalah,<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-600">
                    Kami Tuntaskan.
                </span>
            </h1>
            <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-gray-600">
                Layanan pengaduan masyarakat desa yang cepat, transparan, dan responsif. Laporkan jalan rusak, sampah, atau layanan publik lainnya dengan mudah.
            </p>
            <div class="mt-10 flex items-center justify-center gap-x-6">
                <a href="{{ route('report.create') }}" class="rounded-md bg-green-600 px-8 py-2.5 text-sm font-medium text-white shadow-xs hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 h-10 inline-flex items-center justify-center transition-all">
                    Lapor Sekarang
                </a>
                <a href="{{ route('report.tracking.view') }}" class="inline-flex items-center justify-center h-10 rounded-md px-8 text-sm font-medium leading-6 text-gray-900 group gap-2 hover:text-green-600 transition-colors bg-white hover:bg-gray-50 border border-gray-200">
                    Lacak Laporan <span aria-hidden="true" class="group-hover:translate-x-1 transition-transform">→</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Feature Cards -->
    <div class="mx-auto max-w-7xl px-6 lg:px-8 pb-24">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <div class="bg-white border border-gray-200 rounded-2xl p-8 hover:border-green-200 transition-all duration-300">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-50 border border-green-100 mb-6">
                    <span class="text-2xl">📸</span>
                </div>
                <h3 class="text-lg font-semibold leading-7 text-gray-900">1. Foto & Lapor</h3>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    Ambil foto masalah di sekitar Anda, isi formulir singkat yang tersedia, dan kirim laporan Anda dalam hitungan detik.
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-8 hover:border-green-200 transition-all duration-300">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-50 border border-green-100 mb-6">
                    <span class="text-2xl">⚡</span>
                </div>
                <h3 class="text-lg font-semibold leading-7 text-gray-900">2. Verifikasi Cepat</h3>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    Laporan Anda akan langsung diverifikasi oleh operator desa dan diteruskan ke petugas terkait tanpa birokrasi berbelit.
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-8 hover:border-green-200 transition-all duration-300">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-50 border border-green-100 mb-6">
                    <span class="text-2xl">✅</span>
                </div>
                <h3 class="text-lg font-semibold leading-7 text-gray-900">3. Selesai</h3>
                <p class="mt-4 text-base leading-7 text-gray-600">
                    Pantau progress perbaikan secara real-time hingga masalah selesai ditangani. Anda akan mendapat notifikasi update.
                </p>
            </div>
        </div>
    </div>

    @if($emergencyShortcuts->isNotEmpty())
    <!-- Kontak Darurat -->
    <div class="mx-auto max-w-7xl px-6 lg:px-8 pb-24">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                Kontak Darurat
            </h2>
            <p class="mt-4 text-lg text-gray-600">
                Hubungi langsung layanan darurat yang Anda butuhkan.
            </p>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($emergencyShortcuts as $shortcut)
            <a href="tel:{{ $shortcut->phone_number }}"
               class="bg-white border border-gray-200 rounded-2xl p-6 text-center hover:border-red-200 hover:shadow-sm transition-all duration-300 group">
                @if($shortcut->icon_path)
                    <img src="{{ Storage::url($shortcut->icon_path) }}" alt="{{ $shortcut->name }}" class="h-12 w-12 mx-auto mb-3 rounded-lg object-cover">
                @else
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-50 border border-red-100 mx-auto mb-3">
                        <span class="text-2xl">📞</span>
                    </div>
                @endif
                <h3 class="font-semibold text-gray-900 text-sm">{{ $shortcut->name }}</h3>
                <p class="mt-1 text-lg font-bold text-red-600">{{ $shortcut->phone_number }}</p>
                @if($shortcut->description)
                    <p class="mt-1 text-xs text-gray-500">{{ $shortcut->description }}</p>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Telegram CTA -->
    <div class="mx-auto max-w-7xl px-6 lg:px-8 pb-24">
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-50 to-sky-50 border border-blue-100 px-6 py-12 rounded-3xl sm:px-12 sm:py-16">
            <div class="relative mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    Lebih Suka Chatting?
                </h2>
                <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-gray-600">
                    Anda juga bisa melaporkan masalah langsung melalui Telegram Bot kami.
                    Cukup ketik <strong class="text-blue-700">"LAPOR"</strong> dan ikuti petunjuk selanjutnya.
                </p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="https://t.me/belacallbot" target="_blank" class="flex items-center gap-2 rounded-full bg-blue-500 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 transition-all transform hover:scale-105">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        Chat via Telegram
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
