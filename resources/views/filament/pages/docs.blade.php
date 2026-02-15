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
                        Referensi ini mengikuti perilaku sistem saat ini agar keputusan operasional tidak bertentangan dengan aturan di aplikasi.
                    </p>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Revisi terakhir: {{ now()->format('d M Y') }}
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Alur Inti"
            description="Urutan utama dari laporan masuk sampai ditutup."
        >
            <div class="grid gap-4 md:grid-cols-5">
                <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-4 text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-100">
                    <p class="text-sm font-semibold">1. Laporan Masuk</p>
                    <p class="mt-2 text-sm">
                        Warga membuat laporan via web atau Telegram Bot. Status awal selalu <strong>SUBMITTED</strong>.
                    </p>
                </div>
                <div class="rounded-xl border border-violet-200 bg-violet-50/70 p-4 text-violet-900 dark:border-violet-500/40 dark:bg-violet-500/10 dark:text-violet-100">
                    <p class="text-sm font-semibold">2. Triase Pimpinan</p>
                    <p class="mt-2 text-sm">
                        Lurah/Pimpinan atau Admin memilih operator dan menetapkan prioritas saat assign pertama.
                    </p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                    <p class="text-sm font-semibold">3. Status VERIFIED</p>
                    <p class="mt-2 text-sm">
                        Jika sebelumnya <strong>SUBMITTED</strong>, sistem otomatis ubah ke <strong>VERIFIED</strong> saat assign operator.
                    </p>
                </div>
                <div class="rounded-xl border border-orange-200 bg-orange-50/70 p-4 text-orange-900 dark:border-orange-500/40 dark:bg-orange-500/10 dark:text-orange-100">
                    <p class="text-sm font-semibold">4. Eksekusi Operator</p>
                    <p class="mt-2 text-sm">
                        Operator/Petugas menjalankan pekerjaan dan update status berurutan: <strong>IN_PROGRESS</strong> lalu <strong>RESOLVED</strong>.
                    </p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-100">
                    <p class="text-sm font-semibold">5. Penutupan</p>
                    <p class="mt-2 text-sm">
                        Setelah valid, tiket ditutup di status <strong>CLOSED</strong>. Jika tidak valid, bisa masuk <strong>NEEDS_REVISION</strong> atau <strong>REJECTED</strong> sesuai aturan transisi.
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Aturan Sistem Kritis"
            description="Poin ini wajib jadi acuan saat operasional harian."
        >
            <div class="grid gap-3">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Priority dan assign berjalan bersamaan:</strong> laporan baru masuk dengan <strong>priority kosong</strong>. Priority ditetapkan saat assign pertama oleh Lurah/Pimpinan/Admin.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Priority hanya sekali:</strong> jika laporan sudah punya priority, proses assign berikutnya hanya boleh ganti assignee (priority diabaikan).
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>SLA dihitung saat priority pertama ditetapkan:</strong> deadline respons/penyelesaian dibuat ketika assignment pertama, bukan dari waktu laporan dibuat.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Definisi respon SLA:</strong> respon pertama tercatat saat operator mulai tindak lanjut (<strong>IN_PROGRESS</strong>) atau langsung menandai selesai.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Tracking publik:</strong> detail laporan di halaman tracking baru terbuka setelah verifikasi nomor HP pelapor.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Telegram Bot:</strong> laporan via Telegram Bot mendukung alur teks + koordinat lokasi. Upload foto via bot belum didukung.
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Hak Dan Kewenangan Per Role"
            description="Matriks ini mengikuti policy akses dan action yang tersedia di panel."
        >
            <div class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900/60">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Warga</h3>
                    <div class="mt-3 grid gap-3 text-sm text-gray-700 dark:text-gray-200">
                        <p><strong>Hak:</strong> Membuat laporan via web/Telegram Bot, melihat tracking, dan kirim revisi jika diminta.</p>
                        <p><strong>Tugas:</strong> Isi deskripsi dan lokasi yang jelas, serta verifikasi nomor HP saat membuka detail tracking.</p>
                        <p><strong>Batas:</strong> Tidak punya akses panel admin dan tidak bisa assign atau follow-up status internal.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-violet-200 bg-violet-50/50 p-5 dark:border-violet-500/40 dark:bg-violet-500/10">
                    <h3 class="text-lg font-semibold text-violet-900 dark:text-violet-100">Lurah/Pimpinan</h3>
                    <div class="mt-3 grid gap-3 text-sm text-violet-900 dark:text-violet-100">
                        <p><strong>Hak:</strong> Melihat semua laporan dan melakukan assign operator (single/bulk) termasuk menetapkan priority pertama.</p>
                        <p><strong>Tugas:</strong> Triase backlog, distribusi beban operator, memastikan tiket prioritas tinggi segera ter-assign, serta memberi keputusan <strong>NEEDS_REVISION</strong> / <strong>REJECTED</strong> bila diperlukan.</p>
                        <p><strong>Batas:</strong> Tidak menjalankan status operasional operator seperti <strong>IN_PROGRESS</strong>, <strong>RESOLVED</strong>, dan <strong>CLOSED</strong>.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-5 dark:border-amber-500/40 dark:bg-amber-500/10">
                    <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-100">Operator/Petugas</h3>
                    <div class="mt-3 grid gap-3 text-sm text-amber-900 dark:text-amber-100">
                        <p><strong>Hak:</strong> Follow-up tiket yang di-assign, update status, isi catatan, dan unggah bukti.</p>
                        <p><strong>Tugas:</strong> Menjalankan transisi status sesuai aturan workflow dan menjaga progres tetap ter-update.</p>
                        <p><strong>Batas:</strong> Tidak bisa assign operator atau menetapkan/mengubah priority.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 dark:border-emerald-500/40 dark:bg-emerald-500/10">
                    <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">Admin</h3>
                    <div class="mt-3 grid gap-3 text-sm text-emerald-900 dark:text-emerald-100">
                        <p><strong>Hak:</strong> Akses penuh panel: kelola user, edit laporan, follow-up status, assign, serta konfigurasi master data.</p>
                        <p><strong>Akses SLA:</strong> hanya Admin yang dapat membuka menu <strong>Pengaturan -> Konfigurasi SLA</strong> untuk mengubah target respon dan target selesai per prioritas.</p>
                        <p><strong>Tugas:</strong> Menjaga kebijakan akses, kualitas data, dan intervensi saat eskalasi lintas tim.</p>
                        <p><strong>Batas:</strong> Tetap mengikuti aturan transisi status yang valid pada sistem.</p>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="SLA Dan Akses Konfigurasi"
            description="Panduan ringkas membaca SLA dan siapa yang boleh mengubahnya."
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-sky-200 bg-sky-50/60 p-5 text-sm text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-100">
                    <p class="font-semibold">Cara kerja SLA di tiket</p>
                    <div class="mt-3 grid gap-2">
                        <p>1. SLA aktif saat prioritas pertama kali ditetapkan ketika assign.</p>
                        <p>2. <strong>Respon</strong> dihitung saat operator mulai tindak lanjut pertama.</p>
                        <p>3. <strong>Selesai</strong> dihitung saat tiket masuk <strong>RESOLVED</strong>; jika tiket dibuka ulang maka waktu selesai di-reset.</p>
                        <p>4. Dashboard menampilkan indikator keterlambatan respon dan keterlambatan penyelesaian.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-5 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-100">
                    <p class="font-semibold">Akses menu Konfigurasi SLA</p>
                    <div class="mt-3 grid gap-2">
                        <p><strong>Lokasi menu:</strong> <strong>Pengaturan -> Konfigurasi SLA</strong>.</p>
                        <p><strong>Boleh akses:</strong> Admin.</p>
                        <p><strong>Tidak boleh akses:</strong> Lurah/Pimpinan, Operator, dan Warga.</p>
                        <p><strong>Dampak perubahan:</strong> nilai SLA baru langsung dipakai untuk assignment berikutnya.</p>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="SOP Assign Sampai Selesai"
            description="Urutan operasional yang sesuai dengan action dan policy di panel."
        >
            <div class="grid gap-3">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 1:</strong> Cek laporan baru berstatus <strong>SUBMITTED</strong>.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 2:</strong> Lurah/Pimpinan atau Admin assign operator dan tetapkan priority: <strong>URGENT</strong>, <strong>HIGH</strong>, <strong>MEDIUM</strong>, atau <strong>LOW</strong>.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 3:</strong> Saat assign dilakukan pada laporan <strong>SUBMITTED</strong>, sistem otomatis ubah ke <strong>VERIFIED</strong>.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Catatan triase lurah:</strong> Lurah/Pimpinan dapat menetapkan <strong>NEEDS_REVISION</strong> atau <strong>REJECTED</strong> saat peninjauan laporan.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 4:</strong> Operator mulai kerja: <strong>VERIFIED -> IN_PROGRESS</strong>.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 5:</strong> Setelah tindakan selesai, operator ubah ke <strong>RESOLVED</strong> dan unggah bukti.
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-200">
                    <strong>Langkah 6:</strong> Tutup tiket ke <strong>CLOSED</strong> setelah valid. Jika perlu pengerjaan ulang dari <strong>RESOLVED</strong>, kembalikan ke <strong>IN_PROGRESS</strong>.
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
                        <p>2. Assign + tetapkan priority pada tiket yang belum ter-assign.</p>
                        <p>3. Prioritaskan tiket berdampak tinggi untuk distribusi cepat.</p>
                        <p>4. Pantau tiket overdue dari dashboard pimpinan.</p>
                    </div>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-500/40 dark:bg-amber-500/10">
                    <h3 class="text-base font-semibold text-amber-900 dark:text-amber-100">Checklist Operator</h3>
                    <div class="mt-3 grid gap-2 text-sm text-amber-900 dark:text-amber-100">
                        <p>1. Kerjakan tiket yang sudah di-assign sesuai priority.</p>
                        <p>2. Ikuti urutan status: VERIFIED -> IN_PROGRESS -> RESOLVED -> CLOSED.</p>
                        <p>3. Tambahkan catatan progres dan bukti lapangan.</p>
                        <p>4. Jika hasil belum valid, koordinasikan pembukaan ulang sesuai alur.</p>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
