<x-filament-panels::page>
    <div class="grid gap-6">
        <x-filament::section>
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div class="flex flex-col gap-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Dashboard Harian
                    </p>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Pusat Kendali Operator
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Fokus ke antrian kerja, prioritas laporan, dan hasil penyelesaian.
                    </p>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
        </x-filament::section>

        @php
            $summaryStyles = [
                'border-sky-200 bg-sky-50/70 text-sky-800 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-200',
                'border-amber-200 bg-amber-50/70 text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200',
                'border-rose-200 bg-rose-50/70 text-rose-800 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200',
                'border-indigo-200 bg-indigo-50/70 text-indigo-800 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-200',
                'border-orange-200 bg-orange-50/70 text-orange-800 dark:border-orange-500/40 dark:bg-orange-500/10 dark:text-orange-200',
                'border-emerald-200 bg-emerald-50/70 text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200',
            ];
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            @foreach ($summary as $index => $item)
                @php
                    $cardStyle = $summaryStyles[$index] ?? 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-800/40 dark:text-gray-200';
                @endphp
                <div class="rounded-xl border p-4 {{ $cardStyle }}">
                    <p class="text-sm font-medium">
                        {{ $item['label'] }}
                    </p>
                    <p class="mt-2 text-3xl font-bold">
                        {{ number_format($item['value']) }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-12">
            <div class="xl:col-span-8">
                <x-filament::section
                    heading="Antrian Kerja Utama"
                    description="Daftar laporan yang perlu Anda sentuh dari atas ke bawah."
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800/70">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tiket</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Laporan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Prioritas</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Terakhir Update</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($actionQueueReports as $report)
                                    <tr class="align-top">
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $report['ticket'] }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $report['location'] }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $report['title'] }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $report['hint'] }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-filament::badge color="{{ $report['status_color'] }}">
                                                {{ $report['status_label'] }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-filament::badge color="{{ $report['priority_color'] }}">
                                                {{ $report['priority_label'] }}
                                            </x-filament::badge>
                                            @if ($report['is_response_overdue'])
                                                <div class="mt-2">
                                                    <x-filament::badge color="danger">
                                                        Respon Terlewat
                                                    </x-filament::badge>
                                                </div>
                                            @endif
                                            @if ($report['is_overdue'])
                                                <div class="mt-2">
                                                    <x-filament::badge color="danger">
                                                        Terlambat Update
                                                    </x-filament::badge>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                            {{ $report['updated_at'] }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ $report['url'] }}" class="inline-flex items-center rounded-md border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100 dark:border-primary-500/40 dark:bg-primary-500/10 dark:text-primary-300">
                                                Buka Laporan
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Tidak ada antrian kerja saat ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>

            <div class="xl:col-span-4">
                <div class="grid gap-6">
                    <x-filament::section
                        heading="Alarm Prioritas"
                        description="Laporan Mendesak/Tinggi yang wajib dipantau."
                    >
                        <div class="flex flex-col gap-3">
                            @forelse ($priorityReports as $report)
                                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/60">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-filament::badge color="{{ $report['priority_color'] }}">
                                            {{ $report['priority_label'] }}
                                        </x-filament::badge>
                                        <x-filament::badge color="{{ $report['status_color'] }}">
                                            {{ $report['status_label'] }}
                                        </x-filament::badge>
                                    </div>
                                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $report['title'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $report['ticket'] }}
                                    </p>
                                    <a href="{{ $report['url'] }}" class="mt-3 inline-flex text-xs font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-300">
                                        Lihat detail
                                    </a>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Tidak ada laporan prioritas tinggi.
                                </p>
                            @endforelse
                        </div>
                    </x-filament::section>

                    <x-filament::section
                        heading="Penyelesaian 7 Hari"
                        description="Hasil kerja yang sudah selesai/ditutup."
                    >
                        <div class="flex flex-col gap-3">
                            @forelse ($completedReports as $report)
                                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/60">
                                    <div class="flex items-center justify-between gap-3">
                                        <x-filament::badge color="{{ $report['status_color'] }}">
                                            {{ $report['status_label'] }}
                                        </x-filament::badge>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $report['updated_at'] }}</span>
                                    </div>
                                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $report['title'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $report['ticket'] }}
                                    </p>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada laporan selesai minggu ini.
                                </p>
                            @endforelse
                        </div>
                    </x-filament::section>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
