@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white border border-gray-200 rounded-2xl p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Syarat dan Ketentuan</h1>
            <p class="text-gray-600 mt-2">Terakhir diperbarui: {{ now()->format('d F Y') }}</p>
        </div>

        <div class="prose prose-gray max-w-none">
            <p class="text-gray-700 leading-relaxed">
                Konten syarat dan ketentuan akan ditambahkan di sini.
            </p>
        </div>
    </div>
</div>
@endsection
