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
    @elseif($report)
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
                    {{ $report->status == 'SUBMITTED' ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : '' }}
                    {{ $report->status == 'IN_PROGRESS' ? 'bg-blue-50 text-blue-700 border-blue-200' : '' }}
                    {{ $report->status == 'RESOLVED' ? 'bg-green-50 text-green-700 border-green-200' : '' }}
                ">
                    {{ $report->status }}
                </span>
            </div>
            
            <div class="px-6 py-6 sm:px-8">
                <dl class="space-y-6">
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
                                {{ $report->phone }}
                            </dd>
                        </div>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Deskripsi</dt>
                        <dd class="text-base text-gray-900 bg-gray-50 rounded-xl p-4 border border-gray-100 leading-relaxed">
                            {{ $report->description }}
                        </dd>
                    </div>

                    @if($report->evidence)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-3">Foto Bukti</dt>
                        <dd>
                            <div class="relative group rounded-xl overflow-hidden border border-gray-200 max-w-md">
                                <img src="{{ asset('storage/' . $report->evidence) }}" alt="Bukti Laporan" class="w-full h-64 object-cover transform group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                                    <span class="text-white text-sm font-medium">Klik untuk memperbesar</span>
                                </div>
                            </div>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    @endif
</div>
@endsection
