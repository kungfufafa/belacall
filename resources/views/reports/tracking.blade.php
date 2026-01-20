@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    
    <!-- Search Box -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Lacak Status Laporan</h2>
        <form action="{{ route('report.tracking.view') }}" method="GET" class="flex gap-4">
            <input type="text" name="ticket" value="{{ $ticket }}" placeholder="Masukkan Nomor Tiket (Contoh: T-2023...)" class="flex-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none">
                Cari
            </button>
        </form>
    </div>

    @if(session('success'))
    <div class="rounded-md bg-green-50 p-4 mb-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if($ticket && !$report)
        <div class="bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        Laporan dengan nomor tiket <strong>{{ $ticket }}</strong> tidak ditemukan.
                    </p>
                </div>
            </div>
        </div>
    @elseif($report)
        <!-- Report Detail -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Tiket: #{{ $report->ticket_number }}
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Dibuat pada {{ $report->created_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                    {{ $report->status == 'SUBMITTED' ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $report->status == 'IN_PROGRESS' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $report->status == 'RESOLVED' ? 'bg-green-100 text-green-800' : '' }}
                ">
                    {{ $report->status }}
                </span>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200">
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Judul</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $report->title }}</dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Lokasi</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $report->location_name }}</dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Deskripsi</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $report->description }}</dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Bukti Foto</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @foreach($report->evidences as $evidence)
                                <img src="{{ $evidence->file_path }}" alt="Bukti" class="h-48 w-auto rounded-lg shadow-sm object-cover">
                            @endforeach
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    @endif
</div>
@endsection
