<x-filament-panels::page wire:poll.30s>
    <div class="grid gap-6">
        <x-filament::section>
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div class="flex flex-col gap-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Dashboard Pimpinan
                    </p>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Pusat Kendali Lurah
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Pantau laporan masuk, pembagian petugas, dan kemacetan progres.
                    </p>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
        </x-filament::section>

        @php
            $summaryStyles = [
                'border-violet-200 bg-violet-50/70 text-violet-800 dark:border-violet-500/40 dark:bg-violet-500/10 dark:text-violet-200',
                'border-rose-200 bg-rose-50/70 text-rose-800 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200',
                'border-orange-200 bg-orange-50/70 text-orange-800 dark:border-orange-500/40 dark:bg-orange-500/10 dark:text-orange-200',
                'border-sky-200 bg-sky-50/70 text-sky-800 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-200',
                'border-amber-200 bg-amber-50/70 text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200',
                'border-emerald-200 bg-emerald-50/70 text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200',
                'border-gray-300 bg-gray-50/80 text-gray-700 dark:border-gray-600 dark:bg-gray-800/60 dark:text-gray-200',
            ];
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-7">
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
                    heading="Backlog Penugasan"
                    description="Laporan yang belum memiliki petugas. Segera assign agar tidak menumpuk."
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800/70">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tiket</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Laporan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Prioritas</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Umur Tiket</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($assignmentQueue as $report)
                                    <tr class="align-top">
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $report['ticket'] }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $report['location'] }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $report['title'] }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pelapor: {{ $report['reporter'] }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-filament::badge color="{{ $report['priority_color'] }}">
                                                {{ $report['priority_label'] }}
                                            </x-filament::badge>
                                            <div class="mt-2">
                                                <x-filament::badge color="{{ $report['status_color'] }}">
                                                    {{ $report['status_label'] }}
                                                </x-filament::badge>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                            {{ $report['age'] }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ $report['url'] }}" class="inline-flex items-center rounded-md border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100 dark:border-primary-500/40 dark:bg-primary-500/10 dark:text-primary-300">
                                                Buka Laporan
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Tidak ada backlog penugasan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>

            <div class="xl:col-span-4">
                <x-filament::section
                    heading="Distribusi Beban Operator"
                    description="Lihat petugas mana yang perlu bantuan."
                >
                    <div class="flex flex-col gap-3">
                        @php
                            $maxActive = max(1, collect($operatorLoads)->max('active_count') ?? 1);
                        @endphp
                        @forelse ($operatorLoads as $operator)
                            @php
                                $activePercent = min(100, (int) round(($operator['active_count'] / $maxActive) * 100));
                            @endphp
                            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/60">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $operator['name'] }}
                                    </p>
                                    @if ($operator['overdue_count'] > 0)
                                        <x-filament::badge color="danger">
                                            Overdue {{ $operator['overdue_count'] }}
                                        </x-filament::badge>
                                    @else
                                        <x-filament::badge color="success">
                                            Normal
                                        </x-filament::badge>
                                    @endif
                                </div>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-full rounded-full bg-primary-500" style="width: {{ $activePercent }}%;"></div>
                                </div>
                                <div class="mt-3 flex flex-col gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <p>Tugas aktif: {{ $operator['active_count'] }}</p>
                                    <p>Selesai 7 hari: {{ $operator['completed_week_count'] }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Belum ada data operator.
                            </p>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>
        </div>

        <x-filament::section
            heading="Kasus Butuh Perhatian"
            :description="'Laporan yang tidak ada update lebih dari ' . $overdueDays . ' hari saat status dikerjakan.'"
        >
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($overdueReports as $report)
                    <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-500/40 dark:bg-amber-500/10">
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
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                            {{ $report['ticket'] }} - {{ $report['location'] }}
                        </p>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                            Petugas: {{ $report['assignee'] }}
                        </p>
                        <p class="mt-1 text-xs font-semibold text-amber-700 dark:text-amber-200">
                            Tidak update: {{ $report['age'] }}
                        </p>
                        <a href="{{ $report['url'] }}" class="mt-3 inline-flex text-xs font-semibold text-primary-700 hover:text-primary-600 dark:text-primary-300">
                            Buka laporan
                        </a>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Tidak ada kasus yang butuh perhatian khusus.
                    </p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
