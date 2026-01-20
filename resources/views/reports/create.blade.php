@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Buat Laporan Baru
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Isi data dengan lengkap agar laporan mudah ditindaklanjuti.
            </p>
        </div>
        
        <form action="{{ route('report.store') }}" method="POST" enctype="multipart/form-data" class="px-4 py-5 sm:p-6 space-y-6">
            @csrf

            <!-- Judul -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Judul Laporan</label>
                <input type="text" name="title" id="title" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="Contoh: Jalan Berlubang di RT 01">
            </div>

            <!-- Deskripsi -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi Detail</label>
                <textarea name="description" id="description" rows="3" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="Jelaskan kondisi masalah..."></textarea>
            </div>

            <!-- Lokasi -->
            <div>
                <label for="location_name" class="block text-sm font-medium text-gray-700">Lokasi Kejadian</label>
                <input type="text" name="location_name" id="location_name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="Nama Jalan / Dusun / Patokan">
            </div>

            <!-- Nomor WA -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Nomor WhatsApp Anda</label>
                <input type="tel" name="phone" id="phone" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="08123456789">
                <p class="mt-1 text-xs text-gray-500">Kami akan mengirim notifikasi status laporan ke nomor ini.</p>
            </div>

            <!-- Upload Bukti -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Foto Bukti</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="evidence" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500">
                                <span>Upload a file</span>
                                <input id="evidence" name="evidence" type="file" accept="image/*" class="sr-only" required>
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">
                            PNG, JPG, GIF up to 5MB
                        </p>
                    </div>
                </div>
            </div>

            <div class="pt-5">
                <div class="flex justify-end">
                    <button type="button" onclick="window.history.back()" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                        Batal
                    </button>
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Kirim Laporan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
