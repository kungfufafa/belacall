@extends('layouts.app')

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

    <!-- WhatsApp CTA -->
    <div class="mx-auto max-w-7xl px-6 lg:px-8 pb-24">
        <div class="relative overflow-hidden bg-gradient-to-br from-green-50 to-emerald-50 border border-green-100 px-6 py-12 rounded-3xl sm:px-12 sm:py-16">
            <div class="relative mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    Lebih Suka Chatting?
                </h2>
                <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-gray-600">
                    Anda juga bisa melaporkan masalah langsung melalui WhatsApp Bot kami.
                    Cukup ketik <strong class="text-green-700">"LAPOR"</strong> dan ikuti petunjuk selanjutnya.
                </p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="https://wa.me/6287840490370?text=LAPOR" target="_blank" class="flex items-center gap-2 rounded-full bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 transition-all transform hover:scale-105">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        Chat via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
