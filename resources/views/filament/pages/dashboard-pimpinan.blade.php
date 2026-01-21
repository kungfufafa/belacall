<x-filament-panels::page>
    <div class="grid gap-6">
        <x-filament::section
            heading="Overview Kasus"
            description="Ringkasan real time untuk monitoring."
        >
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($summary as $item)
                    <div class="rounded-xl border border-gray-200/70 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex flex-col gap-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $item['label'] }}
                            </p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($item['value']) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section
                heading="Kasus Overdue"
                :description="'Laporan lebih dari ' . $overdueDays . ' hari tanpa progres.'"
            >
                <div class="flex flex-col gap-3">
                    @forelse ($overdueReports as $report)
                        <div class="flex items-start justify-between gap-4 rounded-lg border border-gray-200/70 p-3 dark:border-gray-700">
                            <div class="flex flex-col gap-1">
                                <a
                                    href="{{ $report['url'] }}"
                                    class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                                >
                                    {{ $report['title'] }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $report['ticket'] }} - {{ $report['location'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Durasi: {{ $report['age'] }} - Petugas: {{ $report['assignee'] }}
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <x-filament::badge color="{{ $report['status_color'] }}">
                                    {{ $report['status_label'] }}
                                </x-filament::badge>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $report['created_at'] }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Tidak ada kasus overdue saat ini.
                        </p>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section
                heading="Aktivitas Terbaru"
                description="Pergerakan terbaru dari seluruh laporan."
            >
                <div class="flex flex-col gap-3">
                    @forelse ($recentReports as $report)
                        <div class="flex items-start justify-between gap-4 rounded-lg border border-gray-200/70 p-3 dark:border-gray-700">
                            <div class="flex flex-col gap-1">
                                <a
                                    href="{{ $report['url'] }}"
                                    class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                                >
                                    {{ $report['title'] }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $report['ticket'] }} - {{ $report['location'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Pelapor: {{ $report['reporter'] }} - Petugas: {{ $report['assignee'] }}
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <x-filament::badge color="{{ $report['status_color'] }}">
                                    {{ $report['status_label'] }}
                                </x-filament::badge>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $report['created_at'] }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Belum ada aktivitas terbaru.
                        </p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
