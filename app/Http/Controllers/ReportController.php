<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Tampilkan form pelaporan
     */
    public function create()
    {
        return view('reports.create');
    }

    /**
     * Simpan laporan dari web
     */
    public function store(Request $request)
    {
        $request->validate([
            'phone' => 'required|numeric|digits_between:10,15',
            'description' => 'required|string|min:10',
            'location_name' => 'required|string',
            'photo' => 'required|image|max:5120', // Max 5MB
        ]);

        try {
            DB::beginTransaction();

            // 1. Cari/Buat User Warga berdasarkan No HP
            // Format HP ke 628xxx biar seragam dgn WA
            $phone = $this->formatPhone($request->phone);

            $user = User::firstOrCreate(
                ['phone' => $phone],
                ['name' => 'Warga '.substr($phone, -4), 'role' => 'warga']
            );

            // 2. Simpan Laporan
            $report = Report::create([
                'ticket_number' => Report::generateTicketNumber(),
                'user_id' => $user->id,
                'title' => substr($request->description, 0, 50).'...',
                'description' => $request->description,
                'location_name' => $request->location_name,
                'status' => 'SUBMITTED',
                'priority' => 'Medium',
            ]);

            // 3. Upload Foto
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('evidences', 'public');

                $report->evidences()->create([
                    'file_path' => Storage::url($path),
                    'file_type' => 'image',
                ]);
            }

            DB::commit();

            return redirect()->route('reports.success', ['ticket' => $report->ticket_number]);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal mengirim laporan: '.$e->getMessage())->withInput();
        }
    }

    public function success($ticket)
    {
        $report = Report::where('ticket_number', $ticket)->firstOrFail();

        return view('reports.success', compact('report'));
    }

    private function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '08')) {
            $phone = '62'.substr($phone, 1);
        }

        return $phone;
    }
}
