<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pengaduan Desa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; }

        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 15px; }
        .header h1 { font-size: 18px; color: #1e3a5f; margin-bottom: 4px; }
        .header h2 { font-size: 13px; color: #2563eb; margin-bottom: 6px; }
        .header .period { font-size: 11px; color: #6b7280; }

        .summary { margin-bottom: 20px; }
        .summary h3 { font-size: 12px; color: #1e3a5f; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .summary-grid { display: table; width: 100%; }
        .summary-row { display: table-row; }
        .summary-cell { display: table-cell; width: 50%; vertical-align: top; padding: 4px 8px; }
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary-table th, .summary-table td { border: 1px solid #d1d5db; padding: 4px 8px; text-align: left; }
        .summary-table th { background-color: #eff6ff; font-weight: bold; color: #1e3a5f; }
        .summary-table td.number { text-align: right; }

        .detail h3 { font-size: 12px; color: #1e3a5f; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table th { background-color: #2563eb; color: #ffffff; padding: 6px 4px; text-align: left; font-size: 9px; }
        .detail-table td { border: 1px solid #d1d5db; padding: 4px; font-size: 9px; }
        .detail-table tr:nth-child(even) { background-color: #f9fafb; }

        .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 9px; color: #6b7280; text-align: center; }

        @page { size: landscape; margin: 15mm; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PENGADUAN DESA</h1>
        <h2>Sistem Pelaporan Warga - BelaCall</h2>
        <div class="period">
            Periode: {{ $period }}
        </div>
    </div>

    <div class="summary">
        <h3>Ringkasan</h3>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell">
                    <table class="summary-table">
                        <tr>
                            <th colspan="2">Statistik Umum</th>
                        </tr>
                        <tr>
                            <td>Total Laporan</td>
                            <td class="number"><strong>{{ $summary['total'] }}</strong></td>
                        </tr>
                        <tr>
                            <td>Kepatuhan SLA</td>
                            <td class="number">{{ $summary['sla_compliance_rate'] }}%</td>
                        </tr>
                        <tr>
                            <td>Rata-rata Penyelesaian</td>
                            <td class="number">{{ $summary['average_resolution_time'] }}</td>
                        </tr>
                    </table>
                </div>
                <div class="summary-cell">
                    <table class="summary-table">
                        <tr>
                            <th>Status</th>
                            <th>Jumlah</th>
                        </tr>
                        @foreach ($summary['by_status'] as $status => $count)
                            <tr>
                                <td>{{ \App\Enums\ReportStatus::tryFrom($status)?->label() ?? $status }}</td>
                                <td class="number">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">
                    <table class="summary-table">
                        <tr>
                            <th>Prioritas</th>
                            <th>Jumlah</th>
                        </tr>
                        @foreach ($summary['by_priority'] as $priority => $count)
                            <tr>
                                <td>{{ \App\Enums\ReportPriority::tryFrom($priority)?->label() ?? $priority }}</td>
                                <td class="number">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div class="summary-cell"></div>
            </div>
        </div>
    </div>

    <div class="detail">
        <h3>Detail Laporan</h3>
        <table class="detail-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 10%;">No. Tiket</th>
                    <th style="width: 15%;">Judul</th>
                    <th style="width: 8%;">Prioritas</th>
                    <th style="width: 8%;">Status</th>
                    <th style="width: 12%;">Lokasi</th>
                    <th style="width: 10%;">Pelapor</th>
                    <th style="width: 10%;">Petugas</th>
                    <th style="width: 10%;">Tanggal Masuk</th>
                    <th style="width: 10%;">Tanggal Selesai</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $index => $report)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $report->ticket_number }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($report->title, 40) }}</td>
                        <td>{{ $report->priority->label() }}</td>
                        <td>{{ $report->status->label() }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($report->location_name, 25) }}</td>
                        <td>{{ $report->user?->name ?? '-' }}</td>
                        <td>{{ $report->assignee?->name ?? '-' }}</td>
                        <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $report->resolved_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 20px;">Tidak ada data laporan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} WIB | Halaman ini dihasilkan secara otomatis oleh sistem BelaCall
    </div>
</body>
</html>
