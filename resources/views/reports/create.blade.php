@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white border border-gray-200 rounded-2xl p-8">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Buat Laporan Baru</h2>
            <p class="text-gray-600 mt-2">Silakan isi formulir di bawah ini dengan lengkap.</p>
        </div>

        <form action="{{ route('report.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
            @csrf

            <!-- Judul -->
            <div>
                <label for="title" class="text-sm font-medium text-gray-700">Judul Laporan <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="title" 
                    class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-transparent px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 disabled:cursor-not-allowed disabled:opacity-50 transition-all font-medium"
                    placeholder="Apa yang ingin Anda laporkan?" required>
            </div>

            <!-- Isi Laporan -->
            <div>
                <label for="description" class="text-sm font-medium text-gray-700">Detail Laporan <span class="text-red-500">*</span></label>
                <textarea name="description" id="description" rows="5" 
                    class="mt-1.5 flex w-full rounded-md border border-gray-200 bg-transparent px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 disabled:cursor-not-allowed disabled:opacity-50 transition-all font-medium resize-none"
                    placeholder="Jelaskan detail kejadian secara lengkap..." required></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Lokasi -->
                <div class="space-y-2">
                    <label for="location_name" class="text-sm font-medium text-gray-700">Lokasi Kejadian <span class="text-red-500">*</span></label>
                    <input type="text" name="location_name" id="location_name" required 
                        class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-transparent px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 disabled:cursor-not-allowed disabled:opacity-50 transition-all font-medium"
                        placeholder="Nama jalan atau patokan">
                </div>

                <!-- Nomor WA -->
                <div>
                    <label for="phone" class="text-sm font-medium text-gray-700">Nomor Telegram <span class="text-red-500">*</span></label>
                    <input type="tel" name="phone" id="phone" required 
                        class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-transparent px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 disabled:cursor-not-allowed disabled:opacity-50 transition-all font-medium"
                        placeholder="Contoh: 08123456789">
                    <p class="mt-1.5 text-xs text-gray-500">Untuk notifikasi update status laporan.</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 flex flex-col gap-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Lokasi GPS (Opsional)</p>
                        <p class="text-xs text-gray-500">Klik tombol untuk mengisi latitude dan longitude otomatis.</p>
                    </div>
                    <button type="button" id="gps-button" class="inline-flex items-center justify-center rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Ambil Lokasi GPS
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="latitude" class="text-sm font-medium text-gray-700">Latitude</label>
                        <input type="text" name="latitude" id="latitude" readonly
                            class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-sm placeholder:text-gray-400 cursor-not-allowed transition-all font-medium"
                            placeholder="-6.200000">
                    </div>

                    <div>
                        <label for="longitude" class="text-sm font-medium text-gray-700">Longitude</label>
                        <input type="text" name="longitude" id="longitude" readonly
                            class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-sm placeholder:text-gray-400 cursor-not-allowed transition-all font-medium"
                            placeholder="106.800000">
                    </div>
                </div>

                <p id="gps-status" class="text-xs text-gray-500">Gunakan tombol di atas untuk mengisi otomatis.</p>
            </div>

            <!-- Bukti Foto -->
            <div>
                <label for="evidence" class="text-sm font-medium text-gray-700">Bukti Foto <span class="text-red-500">*</span></label>
                <div id="upload-zone" class="mt-1.5 flex justify-center rounded-xl border-2 border-dashed border-gray-300 px-6 py-8 transition-all hover:bg-green-50/50 hover:border-green-400 cursor-pointer">
                    <div class="text-center relative w-full">
                        <input id="evidence" name="evidence" type="file" accept="image/*" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        
                        <!-- Preview Container (hidden by default) -->
                        <div id="preview-container" class="hidden">
                            <div class="relative inline-block">
                                <img id="image-preview" src="" alt="Preview" class="max-h-48 rounded-lg shadow-md mx-auto">
                                <button type="button" id="remove-image" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors shadow-md z-20">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <p id="file-name" class="mt-3 text-sm font-medium text-green-600"></p>
                            <p class="text-xs text-gray-500 mt-1">Klik untuk mengganti foto</p>
                        </div>
                        
                        <!-- Upload Placeholder (shown by default) -->
                        <div id="upload-placeholder" class="space-y-3">
                            <!-- Photo Icon -->
                            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-700">
                                    <span class="font-semibold text-green-600 hover:text-green-700">Klik untuk upload</span>
                                    <span class="text-gray-500"> atau drag & drop</span>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    PNG, JPG, JPEG (Maks. 5MB)
                                </p>
                            </div>
                            
                            <!-- Example hint -->
                            <div class="flex items-center justify-center gap-2 text-xs text-gray-400 pt-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Foto membantu mempercepat proses penanganan</span>
                            </div>
                        </div>
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // GPS functionality
        const button = document.getElementById('gps-button');
        const status = document.getElementById('gps-status');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');

        if (button && status && latitudeInput && longitudeInput) {
            const setStatus = (message, tone) => {
                const baseClass = 'text-xs';
                const colorClass = tone === 'error'
                    ? 'text-red-600'
                    : tone === 'success'
                        ? 'text-green-600'
                        : 'text-gray-500';

                status.textContent = message;
                status.className = `${baseClass} ${colorClass}`;
            };

            button.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    setStatus('GPS tidak tersedia di browser ini.', 'error');
                    return;
                }

                button.disabled = true;
                setStatus('Mengambil lokasi GPS...', 'info');

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        latitudeInput.value = position.coords.latitude.toFixed(6);
                        longitudeInput.value = position.coords.longitude.toFixed(6);
                        button.disabled = false;
                        setStatus('Lokasi GPS terisi.', 'success');
                    },
                    () => {
                        button.disabled = false;
                        setStatus('Gagal mengambil lokasi GPS. Pastikan izin lokasi aktif.', 'error');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0,
                    }
                );
            });
        }

        // Image preview functionality
        const fileInput = document.getElementById('evidence');
        const uploadZone = document.getElementById('upload-zone');
        const previewContainer = document.getElementById('preview-container');
        const uploadPlaceholder = document.getElementById('upload-placeholder');
        const imagePreview = document.getElementById('image-preview');
        const fileName = document.getElementById('file-name');
        const removeButton = document.getElementById('remove-image');

        if (fileInput && uploadZone && previewContainer && uploadPlaceholder && imagePreview && fileName && removeButton) {
            const showPreview = (file) => {
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreview.src = e.target.result;
                        fileName.textContent = file.name;
                        previewContainer.classList.remove('hidden');
                        uploadPlaceholder.classList.add('hidden');
                        uploadZone.classList.add('border-green-400', 'bg-green-50/50');
                    };
                    reader.readAsDataURL(file);
                }
            };

            const hidePreview = () => {
                imagePreview.src = '';
                fileName.textContent = '';
                previewContainer.classList.add('hidden');
                uploadPlaceholder.classList.remove('hidden');
                uploadZone.classList.remove('border-green-400', 'bg-green-50/50');
                fileInput.value = '';
            };

            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                showPreview(file);
            });

            removeButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                hidePreview();
            });

            // Drag and drop visual feedback
            uploadZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadZone.classList.add('border-green-500', 'bg-green-100/50');
            });

            uploadZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('border-green-500', 'bg-green-100/50');
            });

            uploadZone.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('border-green-500', 'bg-green-100/50');
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    fileInput.files = e.dataTransfer.files;
                    showPreview(file);
                }
            });
        }
    });
</script>
@endsection
