@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white border border-gray-200 rounded-2xl p-8">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Buat Laporan Baru</h2>
            <p class="text-gray-600 mt-2">Silakan isi formulir di bawah ini dengan lengkap.</p>
        </div>

        <form action="{{ route('report.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Judul -->
            <div>
                <label for="judul" class="text-sm font-medium text-gray-700">Judul Laporan</label>
                <input type="text" name="judul" id="judul" 
                    class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-transparent px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 disabled:cursor-not-allowed disabled:opacity-50 transition-all font-medium"
                    placeholder="Apa yang ingin Anda laporkan?" required>
            </div>

            <!-- Isi Laporan -->
            <div>
                <label for="isi" class="text-sm font-medium text-gray-700">Detail Laporan</label>
                <textarea name="isi" id="isi" rows="5" 
                    class="mt-1.5 flex w-full rounded-md border border-gray-200 bg-transparent px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 disabled:cursor-not-allowed disabled:opacity-50 transition-all font-medium resize-none"
                    placeholder="Jelaskan detail kejadian secara lengkap..." required></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Lokasi -->
                <div class="space-y-2">
                    <label for="location_name" class="text-sm font-medium text-gray-700">Lokasi Kejadian</label>
                    <input type="text" name="location_name" id="location_name" required 
                        class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-transparent px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 disabled:cursor-not-allowed disabled:opacity-50 transition-all font-medium"
                        placeholder="Nama jalan atau patokan">
                </div>

                <!-- Nomor WA -->
                <div>
                    <label for="phone" class="text-sm font-medium text-gray-700">Nomor WhatsApp</label>
                    <input type="tel" name="phone" id="phone" required 
                        class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-transparent px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 disabled:cursor-not-allowed disabled:opacity-50 transition-all font-medium"
                        placeholder="Contoh: 08123456789">
                    <p class="mt-1.5 text-xs text-gray-500">Untuk notifikasi update status laporan.</p>
                </div>
            </div>

            <!-- Bukti Foto -->
            <div>
                <label for="evidence" class="text-sm font-medium text-gray-700">Bukti Foto (Opsional)</label>
                <div class="mt-1.5 flex justify-center rounded-md border border-dashed border-gray-900/25 px-6 py-10 transition-colors hover:bg-gray-50/50">
                    <div class="text-center relative">
                     <input id="evidence" name="evidence" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-green-500 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium text-green-600">Upload file</span>
                            <span class="pl-1">atau drag and drop</span>
                        </div>
                        <p class="text-xs text-gray-500">
                            PNG, JPG hingga 5MB
                        </p>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-4">
                <button type="button" onclick="window.history.back()" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                    Batal
                </button>
                <button type="submit" class="inline-flex justify-center py-3 px-6 border border-transparent text-sm font-bold rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                    Kirim Laporan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
