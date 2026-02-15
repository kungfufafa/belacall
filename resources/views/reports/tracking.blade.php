@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 tracking-tight mb-4">
            Lacak Laporan
        </h1>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">
            Pantau status laporan Anda secara real-time dengan memasukkan nomor tiket.
        </p>
    </div>

    <!-- Search Box -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6 md:p-8 mb-8">
        <form action="{{ route('report.tracking.view') }}" method="GET" class="relative">
            <div class="flex items-center gap-3">
                <div class="relative flex-grow">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <input type="text" name="ticket" 
                        class="block w-full rounded-md border border-gray-200 bg-transparent py-2.5 pl-10 pr-4 text-sm font-medium placeholder:text-gray-400 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-500/20 h-10 transition-all" 
                        placeholder="Contoh: T-2023..." 
                        value="{{ $ticket }}"
                        required>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-green-600 px-6 py-2.5 text-sm font-medium text-white shadow-xs hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none h-10 transition-all">
                    Lacak
                </button>
            </div>
        </form>
    </div>

    @if(session('error'))
    <div class="rounded-xl bg-red-50 border border-red-200 p-4 mb-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="rounded-xl bg-red-50 border border-red-200 p-4 mb-8">
        <div class="flex flex-col gap-2">
            <p class="text-sm font-medium text-red-700">Periksa kembali isian Anda:</p>
            <ul class="flex flex-col gap-1">
                @foreach ($errors->all() as $error)
                    <li class="text-sm text-red-700">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if(session('success'))
    <div class="rounded-xl bg-green-50 border border-green-200 p-4 mb-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-700">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if($ticket && !$report)
        <div class="rounded-xl bg-red-50 border border-red-200 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        Laporan dengan nomor tiket <strong class="text-gray-900">{{ $ticket }}</strong> tidak ditemukan.
                    </p>
                </div>
            </div>
        </div>
    @elseif($report && !$isTrackingVerified)
        <div class="bg-white border border-gray-200 rounded-2xl p-6 md:p-8 flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <h3 class="text-xl font-bold text-gray-900">Verifikasi Nomor HP</h3>
                <p class="text-sm text-gray-600">
                    Demi keamanan data warga, masukkan nomor HP yang digunakan saat melapor untuk melihat detail laporan.
                </p>
            </div>

            <form action="{{ route('report.tracking.verify_phone') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="ticket" value="{{ $report->ticket_number }}">

                <div>
                    <label for="tracking-phone" class="text-sm font-medium text-gray-700">Nomor HP Pelapor</label>
                    <input type="tel" name="phone" id="tracking-phone" required
                        value="{{ old('phone') }}"
                        class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 transition-all font-medium"
                        placeholder="Contoh: 08123456789">
                </div>

                <div class="flex items-center justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-green-700 transition-colors">
                        Verifikasi
                    </button>
                </div>
            </form>
        </div>
    @elseif($report && $isTrackingVerified)
        @php
            $statusLabel = $statusDisplay['label'] ?? (string) $report->status;
            $statusClasses = $statusDisplay['classes'] ?? 'bg-gray-50 text-gray-600 border-gray-200';
        @endphp
        <!-- Report Detail -->
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-5 sm:px-8 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">
                        Tiket: <span class="text-green-600">#{{ $report->ticket_number }}</span>
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Dibuat pada {{ $report->created_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <span class="px-4 py-1.5 inline-flex text-sm font-semibold rounded-full border 
                    {{ $statusClasses }}
                ">
                    {{ $statusLabel }}
                </span>
            </div>
            
            <div class="px-6 py-6 sm:px-8">
                <dl class="flex flex-col gap-6">
                    <div class="group">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Judul Laporan</dt>
                        <dd class="text-lg text-gray-900 font-medium group-hover:text-green-600 transition-colors">{{ $report->title }}</dd>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Lokasi</dt>
                            <dd class="text-base text-gray-900 flex items-start gap-2">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $report->location_name }}
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Pelapor</dt>
                            <dd class="text-base text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>{{ $report->user?->name ?? 'Warga' }}</span>
                                <span class="text-sm text-gray-500">•</span>
                                <span>{{ $report->user?->phone ?? '-' }}</span>
                            </dd>
                        </div>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Deskripsi</dt>
                        <dd class="text-base text-gray-900 bg-gray-50 rounded-xl p-4 border border-gray-100 leading-relaxed">
                            {{ $report->description }}
                        </dd>
                    </div>

                    @if($canRevise)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-3">Perbaiki Laporan</dt>
                            <dd>
                                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 flex flex-col gap-4">
                                    <div class="flex flex-col gap-2">
                                        <p class="text-sm text-amber-700">
                                            Laporan Anda perlu perbaikan sebelum diverifikasi ulang.
                                        </p>
                                        @if($revisionNote)
                                            <p class="text-sm text-amber-700">
                                                Catatan petugas: {{ $revisionNote }}
                                            </p>
                                        @endif
                                    </div>

                                    <form action="{{ route('report.tracking.revision') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                                        @csrf
                                        <input type="hidden" name="ticket" value="{{ $report->ticket_number }}">

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="md:col-span-2">
                                                <label for="revision-description" class="text-sm font-medium text-gray-700">Detail Laporan</label>
                                                <textarea name="description" id="revision-description" rows="4"
                                                    class="mt-1.5 flex w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 transition-all font-medium resize-none"
                                                    placeholder="Tambahkan detail yang kurang">{{ old('description', $report->description) }}</textarea>
                                            </div>

                                            <div>
                                                <label for="revision-location" class="text-sm font-medium text-gray-700">Lokasi Kejadian</label>
                                                <input type="text" name="location_name" id="revision-location"
                                                    value="{{ old('location_name', $report->location_name) }}"
                                                    class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 transition-all font-medium"
                                                    placeholder="Nama jalan atau patokan">
                                            </div>

                                            <div>
                                                <label for="revision-notes" class="text-sm font-medium text-gray-700">Catatan untuk Petugas</label>
                                                <textarea name="notes" id="revision-notes" rows="2"
                                                    class="mt-1.5 flex w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 transition-all font-medium resize-none"
                                                    placeholder="Jelaskan perbaikan yang Anda kirim">{{ old('notes') }}</textarea>
                                            </div>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white p-3 flex flex-col gap-3">
                                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-700">Lokasi GPS (Opsional)</p>
                                                    <p class="text-xs text-gray-500">Klik tombol untuk mengisi koordinat otomatis.</p>
                                                </div>
                                                <button type="button" id="revision-gps-button" class="inline-flex items-center justify-center rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                                                    Ambil Lokasi GPS
                                                </button>
                                            </div>
                                            <p id="revision-gps-status" class="text-xs text-gray-500">Isi manual jika diperlukan.</p>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label for="revision-latitude" class="text-sm font-medium text-gray-700">Latitude</label>
                                                <input type="number" name="latitude" id="revision-latitude" step="any"
                                                    value="{{ old('latitude', $report->latitude) }}"
                                                    class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 transition-all font-medium"
                                                    placeholder="-6.200000">
                                            </div>

                                            <div>
                                                <label for="revision-longitude" class="text-sm font-medium text-gray-700">Longitude</label>
                                                <input type="number" name="longitude" id="revision-longitude" step="any"
                                                    value="{{ old('longitude', $report->longitude) }}"
                                                    class="mt-1.5 flex h-10 w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-600 transition-all font-medium"
                                                    placeholder="106.800000">
                                            </div>
                                        </div>

                                        <div>
                                            <label for="revision-evidence" class="text-sm font-medium text-gray-700">Bukti Tambahan (Opsional)</label>
                                            <input type="file" name="evidence" id="revision-evidence" accept="image/*"
                                                class="mt-1.5 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-green-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-green-700">
                                        </div>

                                        <div class="flex items-center justify-end gap-3">
                                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-green-700 transition-colors">
                                                Kirim Perbaikan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-3">Bukti Laporan</dt>
                        <dd>
                            @if(count($evidenceItems))
                                <div class="grid gap-4 sm:grid-cols-2">
                                    @foreach ($evidenceItems as $evidence)
                                        <a href="{{ $evidence['url'] }}" target="_blank" rel="noopener" class="block rounded-xl border border-gray-200 overflow-hidden bg-white hover:shadow-sm transition-shadow">
                                            @if($evidence['is_image'])
                                                <div class="relative h-48 bg-gray-100">
                                                    <img src="{{ $evidence['url'] }}" alt="{{ $evidence['name'] }}" class="h-full w-full object-cover">
                                                </div>
                                            @endif
                                            <div class="flex flex-col gap-2 p-4">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-sm font-semibold text-gray-900 truncate">
                                                        {{ $evidence['name'] }}
                                                    </span>
                                                    <span class="text-xs rounded-full border px-2 py-0.5 {{ $evidence['type'] === 'IMAGE' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                                        {{ $evidence['type'] }}
                                                    </span>
                                                </div>
                                                <span class="text-xs text-gray-500">
                                                    {{ $evidence['created_at'] }}
                                                </span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500">Belum ada bukti yang diunggah.</p>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-3">Riwayat Status</dt>
                        <dd>
                            @if(count($timeline))
                                <div class="flex flex-col gap-4">
                                    @foreach ($timeline as $item)
                                        <div class="flex gap-4">
                                            <div class="mt-2 h-2.5 w-2.5 rounded-full bg-green-500"></div>
                                            <div class="flex-1 rounded-xl border border-gray-200 bg-white p-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-sm font-semibold text-gray-900">
                                                        {{ $item['title'] }}
                                                    </p>
                                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full border {{ $item['badge_classes'] }}">
                                                        {{ $item['badge'] }}
                                                    </span>
                                                </div>
                                                <p class="mt-2 text-sm text-gray-600">
                                                    {{ $item['description'] }}
                                                </p>
                                                <p class="mt-2 text-xs text-gray-500">
                                                    {{ $item['actor'] }} • {{ $item['created_at'] }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500">Belum ada aktivitas untuk laporan ini.</p>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const button = document.getElementById('revision-gps-button');
        const status = document.getElementById('revision-gps-status');
        const latitudeInput = document.getElementById('revision-latitude');
        const longitudeInput = document.getElementById('revision-longitude');

        if (!button || !status || !latitudeInput || !longitudeInput) {
            return;
        }

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
    });
</script>
@endsection
