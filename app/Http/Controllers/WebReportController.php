<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebReportController extends Controller
{
    public function index()
    {
        return view('landing');
    }

    public function create()
    {
        return view('reports.create');
    }

    public function store(Request $request, FonnteService $fonnteService)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'phone' => 'required|string|max:15', // Nomor WA pelapor
            'location_name' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'evidence' => 'required|image|max:5120', // Max 5MB
        ]);

        // 1. Handle User (Warga)
        // Format nomor HP ke 62
        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['name' => 'Warga ' . substr($phone, -4), 'role' => 'warga']
        );

        // 2. Upload Bukti
        $path = $request->file('evidence')->store('evidences', 'public');
        $fullPath = asset('storage/' . $path); // URL Publik untuk Fonnte/Web

        // 3. Buat Report
        $report = Report::create([
            'ticket_number' => 'T-' . now()->format('YmdHi') . rand(10,99),
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'location_name' => $request->location_name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'SUBMITTED',
            'category' => 'General'
        ]);

        $report->evidences()->create([
            'file_path' => $fullPath,
            'file_type' => 'image'
        ]);

        // 4. Notifikasi WA ke Pelapor (Opsional tapi bagus)
        $msg = "✅ Laporan Web Diterima!\nTiket: *{$report->ticket_number}*\nSimpan nomor ini untuk tracking.";
        $fonnteService->sendText($phone, $msg);

        return redirect()->route('report.tracking.view', ['ticket' => $report->ticket_number])
            ->with('success', 'Laporan berhasil dikirim! Silakan simpan nomor tiket Anda.');
    }

    public function trackingView(Request $request)
    {
        $ticket = $request->query('ticket');
        $report = null;

        if ($ticket) {
            $report = Report::with(['evidences', 'user'])->where('ticket_number', $ticket)->first();
        }

        return view('reports.tracking', compact('report', 'ticket'));
    }
}
