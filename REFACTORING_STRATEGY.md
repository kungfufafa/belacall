# Refactoring Strategy: Robust Domain Logic Implementation

Dokumen ini merinci strategi untuk mengubah logika bisnis yang "naif" (tersebar di Controller/UI) menjadi sistem yang robust, aman, dan mendukung **assignment berjenjang** dengan notifikasi yang terintegrasi.

## 1. Identifikasi Kerentanan & Gap (Audit Findings)

Berdasarkan audit codebase, ditemukan kerentanan dan kekurangan fitur berikut:

### A. Race Conditions (Kritis)
*   **Temuan**: Tidak ada penggunaan `DB::transaction()` atau `lockForUpdate()` pada operasi krusial seperti update status dan assignment.
*   **Risiko**: Data inconsistency, orphaned records, partial updates pada bulk action.

### B. State Integrity (Inkonsistensi Status)
*   **Temuan**: Perubahan status dilakukan langsung tanpa validasi transisi.
*   **Risiko**: Laporan bisa melompat status secara ilegal (misal: `SUBMITTED` -> `CLOSED` tanpa verifikasi).

### C. Tiered Assignment Gaps (Assignment Berjenjang)
*   **Temuan**: Sistem assignment saat ini bersifat "flat" (Pimpinan -> Operator). Belum ada mekanisme delegasi berjenjang (misal: Admin Pusat -> Koordinator Wilayah -> Petugas Lapangan).
*   **Risiko**: Kesulitan manajemen pada skala besar; satu pimpinan harus mengelola semua assignment.

### D. Notification Inconsistency
*   **Temuan**: Feedback ke user masih manual (session flash) dan belum memanfaatkan **Filament Notifications** secara optimal untuk real-time feedback dan history.
*   **Risiko**: UX yang kurang responsif dan informatif.

---

## 2. Proposed Architecture: Service Layer & Tiered System

Kami merekomendasikan pendekatan **Service Layer** yang didukung oleh **Assignment Service** khusus untuk menangani hierarki dan **Notification System** native Filament.

### Struktur Baru
```
app/
├── Services/
│   ├── ReportService.php       <-- Core Logic (Status, CRUD)
│   ├── AssignmentService.php   <-- NEW: Menangani Logika Assignment Berjenjang
│   └── ReportStateMachine.php  <-- Validasi Transisi Status
├── Notifications/
│   └── Filament/               <-- Filament-specific Notifications (Database & Broadcast)
```

### Komponen 1: AssignmentService (Tiered Logic)
Service ini menangani logika "siapa bisa menugaskan ke siapa".

```php
class AssignmentService
{
    public function assign(Report $report, User $assignee, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($report, $assignee, $actor, $note) {
            // 1. Hierarchy Validation
            // Pastikan actor punya wewenang menugaskan ke assignee
            if (! $this->canAssign($actor, $assignee)) {
                throw new UnauthorizedAssignmentException("Anda tidak dapat menugaskan laporan ke user ini.");
            }

            // 2. Locking & Update
            $report = $report->lockForUpdate()->find($report->id);
            $previousAssignee = $report->assignee;
            
            $report->update(['assignee_id' => $assignee->id]);

            // 3. Log History
            $this->logHistory($report, 'ASSIGNMENT', $actor, "Assigned from " . ($previousAssignee?->name ?? 'None') . " to {$assignee->name}");

            // 4. Notifications (Filament)
            $this->sendAssignmentNotifications($report, $assignee, $actor);
        });
    }

    private function sendAssignmentNotifications($report, $assignee, $actor)
    {
        // Notifikasi ke Assignee (Target)
        Notification::make()
            ->title('Tugas Baru Diterima')
            ->body("Anda telah ditugaskan laporan #{$report->id} oleh {$actor->name}")
            ->actions([
                Action::make('view')->url(ReportResource::getUrl('view', ['record' => $report])),
            ])
            ->sendToDatabase($assignee); // Simpan ke lonceng notifikasi
            
        // Notifikasi Balik ke Actor (Success Feedback)
        Notification::make()
            ->title('Assignment Berhasil')
            ->success()
            ->send(); // Toast message di UI
    }
}
```

### Komponen 2: ReportService (Status & Flow)
Menggunakan Filament Notification untuk feedback status.

```php
class ReportService
{
    public function updateStatus(Report $report, ReportStatus $newStatus, ?User $actor): void
    {
        // ... transaction logic ...
        
        // UI Feedback
        Notification::make()
            ->title("Status diperbarui ke {$newStatus->label()}")
            ->success()
            ->send();
            
        // Notify Reporter (jika perlu)
        // ...
    }
}
```

---

## 3. Langkah Refactoring (Step-by-Step)

### Phase 1: Foundation (Services)
1.  Buat `app/Services/AssignmentService.php`.
    *   Implementasikan method `canAssign($actor, $target)` untuk mengecek hierarki (bisa via Role atau field `supervisor_id` di User).
    *   Implementasikan `assign` dengan Transaction.
2.  Buat `app/Services/ReportService.php`.
    *   Pindahkan logic update status ke sini.
    *   Gunakan `ReportStateMachine` untuk validasi.

### Phase 2: Filament Integration (Assignment Berjenjang)
1.  **Refactor Action `AssignOperator` di `ViewReport`**:
    *   Ubah query `options()` pada Select User agar hanya menampilkan bawahan dari user yang sedang login (Actor).
    *   Gunakan `AssignmentService::assign()` pada `action()`.
2.  **Refactor Bulk Action**:
    *   Terapkan logic filtering user yang sama untuk Bulk Assignment.

### Phase 3: Notifications Upgrade
1.  Pastikan `AdminPanelProvider` mengaktifkan database notifications: `->databaseNotifications()`.
2.  Ganti semua penggunaan `session()->flash()` atau `->notify()` manual dengan **`Filament\Notifications\Notification`**.
3.  Pastikan notifikasi tersimpan di database untuk history, dan muncul sebagai Toast untuk feedback instan.

### Phase 4: Cleanup & Policy
1.  Update `ReportPolicy`: Pastikan logic `viewAny` mendukung hierarki (User hanya bisa melihat laporan yang ditugaskan ke dia atau bawahannya).
2.  Hapus controller logic lama yang duplikat.

---

## 4. Keuntungan
1.  **Hierarki Jelas**: Mendukung struktur organisasi (Pusat -> Wilayah -> Petugas).
2.  **User Experience**: Notifikasi yang konsisten (Toast + History) memberitahu user apa yang terjadi.
3.  **Data Integrity**: Tidak ada assignment ilegal atau update status yang melewati prosedur.
