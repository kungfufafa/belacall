@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Kebijakan Privasi</h1>
        <p class="text-sm text-gray-500">Terakhir diperbarui: {{ date('d M Y') }}</p>
    </div>

    <div class="prose prose-slate max-w-none">
        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">1. Pendahuluan</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                BELACALL adalah sistem pelaporan masyarakat yang dikelola oleh Pemerintah Desa untuk menerima, memproses, dan menindaklanjuti laporan dari warga. Kebijakan privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi informasi pribadi Anda saat menggunakan layanan kami, termasuk website pelaporan, integrasi Telegram, dan sistem autentikasi magic link.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">2. Informasi yang Kami Kumpulkan</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Kami mengumpulkan informasi berikut saat Anda mengirimkan laporan:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Data Identitas:</strong> Nama lengkap dan nomor telepon untuk identifikasi dan komunikasi</li>
                <li><strong>Lokasi:</strong> Alamat atau koordinat lokasi kejadian yang dilaporkan</li>
                <li><strong>Detail Laporan:</strong> Deskripsi laporan, foto, dokumen pendukung, kategori, dan tingkat prioritas</li>
                <li><strong>Data Teknis:</strong> Alamat IP, jenis perangkat, browser, dan timestamp aktivitas</li>
                <li><strong>Data Telegram:</strong> Chat ID, username Telegram (jika menggunakan bot)</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">3. Penggunaan Informasi</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Informasi yang dikumpulkan digunakan untuk:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li>Memproses dan menindaklanjuti laporan Anda sesuai prosedur yang berlaku</li>
                <li>Mengirimkan notifikasi status laporan melalui SMS dan Telegram</li>
                <li>Memantau dan mengelola Service Level Agreement (SLA)</li>
                <li>Mengirimkan magic link untuk autentikasi tanpa password</li>
                <li>Menganalisis pola laporan untuk peningkatan layanan</li>
                <li>Berkomunikasi dengan Anda terkait klarifikasi atau revisi laporan</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">4. Integrasi Telegram</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                BELACALL terintegrasi dengan Telegram Bot untuk mempermudah pelaporan dan notifikasi:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Interaksi Bot:</strong> Pesan yang Anda kirim ke bot Telegram kami akan direkam untuk pemrosesan laporan</li>
                <li><strong>Notifikasi:</strong> Anda akan menerima notifikasi otomatis terkait status laporan melalui Telegram</li>
                <li><strong>Data yang Disimpan:</strong> Chat ID, username, riwayat percakapan terkait laporan</li>
                <li><strong>Pengaturan:</strong> Anda dapat menghentikan notifikasi Telegram kapan saja</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">5. Magic Link Authentication</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Untuk keamanan dan kemudahan akses, kami menggunakan sistem autentikasi magic link:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Tanpa Password:</strong> Anda tidak perlu mengingat password untuk mengakses sistem</li>
                <li><strong>Link Sekali Pakai:</strong> Magic link yang dikirim hanya dapat digunakan sekali dan memiliki waktu kadaluarsa</li>
                <li><strong>Pengiriman:</strong> Link autentikasi dikirim ke nomor telepon atau email terdaftar</li>
                <li><strong>Keamanan:</strong> Link ditandatangani secara kriptografis untuk mencegah pemalsuan</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">6. Penyimpanan dan Keamanan Data</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Kami berkomitmen melindungi data Anda dengan:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Enkripsi:</strong> Data dienkripsi saat transmisi (HTTPS) dan penyimpanan</li>
                <li><strong>Akses Terbatas:</strong> Hanya petugas yang berwenang dapat mengakses data laporan</li>
                <li><strong>Retensi Data:</strong> Data laporan disimpan sesuai periode retensi yang ditetapkan regulasi</li>
                <li><strong>Monitoring:</strong> Sistem dipantau untuk mendeteksi aktivitas mencurigakan</li>
                <li><strong>Backup:</strong> Data dicadangkan secara berkala untuk mencegah kehilangan</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">7. Hak Warga</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Anda memiliki hak berikut terkait data pribadi Anda:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Akses:</strong> Meminta salinan data pribadi yang kami simpan</li>
                <li><strong>Koreksi:</strong> Memperbarui atau memperbaiki data yang tidak akurat</li>
                <li><strong>Penghapusan:</strong> Meminta penghapusan data (dengan batasan regulasi)</li>
                <li><strong>Portabilitas:</strong> Meminta transfer data ke format yang dapat dibaca mesin</li>
                <li><strong>Pembatasan:</strong> Meminta pembatasan pemrosesan data tertentu</li>
            </ul>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Untuk menggunakan hak-hak ini, hubungi kami melalui informasi kontak di bawah.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">8. SLA dan Komitmen Layanan</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Kami berkomitmen pada standar layanan berikut:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Respons Cepat:</strong> Laporan darurat ditanggapi dalam waktu yang ditentukan SLA</li>
                <li><strong>Tracking Transparan:</strong> Anda dapat memantau status laporan melalui website</li>
                <li><strong>Notifikasi Berkala:</strong> Pembaruan status dikirim melalui Telegram/SMS</li>
                <li><strong>Eskalasi:</strong> Laporan yang melampaui SLA akan dieskalasi otomatis</li>
                <li><strong>Pelaporan:</strong> Laporan SLA tersedia untuk transparansi kinerja</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">9. Kontak</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Untuk pertanyaan terkait kebijakan privasi atau data pribadi Anda, hubungi:
            </p>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-base text-gray-700 mb-2"><strong>Pemerintah Desa - BELACALL</strong></p>
                <p class="text-sm text-gray-600 mb-1">Email: admin@desa.go.id</p>
                <p class="text-sm text-gray-600 mb-1">Telepon: (xxx) xxx-xxxx</p>
                <p class="text-sm text-gray-600">Alamat: Kantor Desa [Nama Desa]</p>
            </div>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">10. Perubahan Kebijakan</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Kebijakan privasi ini dapat diperbarui sewaktu-waktu untuk mencerminkan perubahan regulasi atau praktik kami. Perubahan akan diumumkan di halaman ini dengan tanggal pembaruan terbaru. Kami menyarankan Anda meninjau kebijakan ini secara berkala.
            </p>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Dengan menggunakan layanan BELACALL, Anda menyetujui pengumpulan dan penggunaan informasi sesuai kebijakan ini.
            </p>
        </section>
    </div>
</div>
@endsection
