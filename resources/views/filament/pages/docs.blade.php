<x-filament-panels::page>
    <div class="grid gap-6">
        <x-filament::section>
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div class="flex flex-col gap-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Panduan Operasional
                    </p>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Panduan Peran Pelaporan Warga
                    </h2>
                    <p class="text-base text-gray-700 dark:text-gray-200">
                        Halaman ini menjelaskan hak, tugas, dan batas kewenangan tiap role agar alur kerja konsisten.
                    </p>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Revisi terakhir: {{ now()->format('d M Y') }}
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Alur Inti"
            description="Urutan utama dari laporan masuk hingga selesai."
        >
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-4 text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-100">
                    <p class="text-sm font-semibold">1. Warga Melapor</p>
                    <p class="mt-2 text-sm">
                        Warga kirim laporan via web atau WhatsApp Fonnte. Status awal: <strong>SUBMITTED</strong>.
                    </p>
                </div>
                <div class="rounded-xl border border-violet-200 bg-violet-50/70 p-4 text-violet-900 dark:border-violet-500/40 dark:bg-violet-500/10 dark:text-violet-100">
                    <p class="text-sm font-semibold">2. Lurah Menilai</p>
                    <p class="mt-2 text-sm">
                        Lurah menentukan prioritas dan assign operator. Sistem ubah status ke <strong>VERIFIED</strong>.
                    </p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                    <p class="text-sm font-semibold">3. Operator Mengerjakan</p>
                    <p class="mt-2 text-sm">
                        Operator memproses laporan, update progres, dan unggah bukti. Status bergerak ke <strong>IN_PROGRESS</strong>.
                    </p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-100">
                    <p class="text-sm font-semibold">4. Selesai</p>
                    <p class="mt-2 text-sm">
                        Setelah pekerjaan tuntas, operator ubah ke <strong>RESOLVED</strong> lalu tutup ke <strong>CLOSED</strong>.
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Hak Dan Kewajiban Per Role"
            description="Ringkasan fokus kerja agar tidak tumpang tindih."
        >
            <div class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900/60">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Warga</h3>
                    <div class="mt-3 grid gap-3 text-sm text-gray-700 dark:text-gray-200">
                        <p><strong>Hak:</strong> Buat laporan, kirim bukti, cek progres tiket.</p>
                        <p><strong>Tugas:</strong> Isi deskripsi jelas, lokasi akurat, dan data pendukung yang valid.</p>
                        <p><strong>Batas:</strong> Tidak dapat assign petugas atau ubah prioritas/status internal.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-violet-200 bg-violet-50/50 p-5 dark:border-violet-500/40 dark:bg-violet-500/10">
                    <h3 class="text-lg font-semibold text-violet-900 dark:text-violet-100">Lurah/Pimpinan</h3>
                    <div class="mt-3 grid gap-3 text-sm text-violet-900 dark:text-violet-100">
                        <p><strong>Hak:</strong> Melihat seluruh laporan, menentukan prioritas, dan assign operator.</p>
                        <p><strong>Tugas:</strong> Triase backlog harian, seimbangkan beban operator, pantau laporan terlambat.</p>
                        <p><strong>Batas:</strong> Tidak menutup tiket operasional. Penutupan akhir dilakukan operator.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-5 dark:border-amber-500/40 dark:bg-amber-500/10">
                    <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-100">Operator/Petugas</h3>
                    <div class="mt-3 grid gap-3 text-sm text-amber-900 dark:text-amber-100">
                        <p><strong>Hak:</strong> Menindaklanjuti tiket yang di-assign, update status kerja, unggah bukti.</p>
                        <p><strong>Tugas:</strong> Kerjakan laporan sesuai prioritas, beri catatan progres, selesaikan sampai close.</p>
                        <p><strong>Batas:</strong> Tidak menetapkan prioritas dan tidak assign operator lain.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 dark:border-emerald-500/40 dark:bg-emerald-500/10">
                    <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">Admin</h3>
                    <div class="mt-3 grid gap-3 text-sm text-emerald-900 dark:text-emerald-100">
                        <p><strong>Hak:</strong> Kelola user, data master, audit, dan konfigurasi sistem.</p>
                        <p><strong>Tugas:</strong> Menjaga stabilitas platform, keamanan data, dan aturan akses.</p>
                        <p><strong>Batas:</strong> Intervensi operasional hanya bila eskalasi atau kebijakan khusus.</p>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="SOP Lurah Ke Operator Sampai Selesai"
            description="Gunakan urutan ini agar proses pelaporan rapi dan bisa dipertanggungjawabkan."
        >
            <div class="grid gap-3">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 1:</strong> Cek laporan baru berstatus <strong>SUBMITTED</strong>.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 2:</strong> Lurah pilih prioritas: <strong>URGENT</strong>, <strong>HIGH</strong>, <strong>MEDIUM</strong>, atau <strong>LOW</strong>.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 3:</strong> Lurah assign operator yang paling siap. Sistem otomatis ubah status ke <strong>VERIFIED</strong>.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 4:</strong> Operator mulai kerja dan ubah status ke <strong>IN_PROGRESS</strong>.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 5:</strong> Jika tindakan selesai, operator ubah ke <strong>RESOLVED</strong> dan unggah bukti lapangan.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 6:</strong> Setelah verifikasi akhir internal, operator tutup laporan ke <strong>CLOSED</strong>.
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Checklist Harian"
            description="Panduan singkat yang bisa langsung dipakai tim."
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-violet-200 bg-violet-50/60 p-4 dark:border-violet-500/40 dark:bg-violet-500/10">
                    <h3 class="text-base font-semibold text-violet-900 dark:text-violet-100">Checklist Lurah</h3>
                    <div class="mt-3 grid gap-2 text-sm text-violet-900 dark:text-violet-100">
                        <p>1. Cek backlog laporan baru minimal 2 kali sehari.</p>
                        <p>2. Set prioritas berdasarkan dampak dan urgensi warga.</p>
                        <p>3. Assign ke operator dengan beban paling seimbang.</p>
                        <p>4. Pantau tiket yang lama tidak update.</p>
                    </div>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-500/40 dark:bg-amber-500/10">
                    <h3 class="text-base font-semibold text-amber-900 dark:text-amber-100">Checklist Operator</h3>
                    <div class="mt-3 grid gap-2 text-sm text-amber-900 dark:text-amber-100">
                        <p>1. Ambil tiket prioritas tertinggi terlebih dulu.</p>
                        <p>2. Ubah status saat mulai kerja agar dashboard akurat.</p>
                        <p>3. Tambahkan catatan dan bukti pekerjaan.</p>
                        <p>4. Pastikan tiket ditutup setelah pekerjaan benar-benar selesai.</p>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
