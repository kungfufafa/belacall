# Privacy and Terms Pages Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add Privacy Policy and Terms of Service pages with comprehensive Indonesian legal content accessible from footer

**Architecture:** Simple LegalController with two methods (privacy, terms) returning Blade views. Routes are publicly accessible. Footer links updated to use route helpers. Content covers standard reporting, Telegram integration, magic link auth, and SLA commitments.

**Tech Stack:** Laravel 11, Blade templates, Tailwind CSS

---

## Task 1: Create LegalController

**Files:**
- Create: `app/Http/Controllers/LegalController.php`
- Test: `tests/Feature/LegalPagesTest.php`

**Step 1: Write failing test for privacy page**

Create `tests/Feature/LegalPagesTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_privacy_page_can_be_accessed(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertStatus(200);
        $response->assertViewIs('legal.privacy');
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LegalPagesTest.php`
Expected: FAIL with "Route [legal.privacy] not defined"

**Step 3: Create LegalController**

Create `app/Http/Controllers/LegalController.php`:

```php
<?php

namespace App\Http\Controllers;

class LegalController extends Controller
{
    public function privacy()
    {
        return view('legal.privacy');
    }
}
```

**Step 4: Add privacy route**

Modify `routes/web.php` after line 7 (after the home route):

```php
use App\Http\Controllers\LegalController;

// Legal Pages
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
```

**Step 5: Create placeholder privacy view**

Create `resources/views/legal/privacy.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">Kebijakan Privasi</h1>
    <p class="text-gray-600">Content coming soon...</p>
</div>
@endsection
```

**Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/LegalPagesTest.php`
Expected: PASS

**Step 7: Commit**

```bash
git add app/Http/Controllers/LegalController.php routes/web.php resources/views/legal/privacy.blade.php tests/Feature/LegalPagesTest.php
git commit -m "feat: add privacy page with controller and route"
```

---

## Task 2: Add Terms Page

**Files:**
- Modify: `app/Http/Controllers/LegalController.php`
- Modify: `routes/web.php`
- Create: `resources/views/legal/terms.blade.php`
- Modify: `tests/Feature/LegalPagesTest.php`

**Step 1: Write failing test for terms page**

Add to `tests/Feature/LegalPagesTest.php`:

```php
    public function test_terms_page_can_be_accessed(): void
    {
        $response = $this->get(route('legal.terms'));

        $response->assertStatus(200);
        $response->assertViewIs('legal.terms');
    }
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LegalPagesTest.php`
Expected: FAIL with "Route [legal.terms] not defined"

**Step 3: Add terms method to controller**

Add to `app/Http/Controllers/LegalController.php`:

```php
    public function terms()
    {
        return view('legal.terms');
    }
```

**Step 4: Add terms route**

Add to `routes/web.php` after privacy route:

```php
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
```

**Step 5: Create placeholder terms view**

Create `resources/views/legal/terms.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">Syarat dan Ketentuan</h1>
    <p class="text-gray-600">Content coming soon...</p>
</div>
@endsection
```

**Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/LegalPagesTest.php`
Expected: PASS (both tests)

**Step 7: Commit**

```bash
git add app/Http/Controllers/LegalController.php routes/web.php resources/views/legal/terms.blade.php tests/Feature/LegalPagesTest.php
git commit -m "feat: add terms page with controller method and route"
```

---

## Task 3: Update Footer Links

**Files:**
- Modify: `resources/views/layouts/app.blade.php:74-75`
- Modify: `tests/Feature/LegalPagesTest.php`

**Step 1: Write test for footer links**

Add to `tests/Feature/LegalPagesTest.php`:

```php
    public function test_footer_contains_privacy_and_terms_links(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee(route('legal.privacy'));
        $response->assertSee(route('legal.terms'));
    }
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LegalPagesTest.php::test_footer_contains_privacy_and_terms_links`
Expected: FAIL

**Step 3: Update footer links**

Modify `resources/views/layouts/app.blade.php` lines 74-75:

```blade
                    <a href="{{ route('legal.privacy') }}" class="text-gray-500 hover:text-gray-900 transition-colors text-sm">Privacy</a>
                    <a href="{{ route('legal.terms') }}" class="text-gray-500 hover:text-gray-900 transition-colors text-sm">Terms</a>
```

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/LegalPagesTest.php::test_footer_contains_privacy_and_terms_links`
Expected: PASS

**Step 5: Commit**

```bash
git add resources/views/layouts/app.blade.php tests/Feature/LegalPagesTest.php
git commit -m "feat: add privacy and terms links to footer"
```

---

## Task 4: Write Privacy Policy Content

**Files:**
- Modify: `resources/views/legal/privacy.blade.php`

**Step 1: Replace privacy view with complete content**

Replace entire `resources/views/legal/privacy.blade.php`:

```blade
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
```

**Step 2: Verify privacy page renders**

Run: `php artisan serve`
Visit: `http://localhost:8000/privacy`
Expected: Page renders with complete content

**Step 3: Commit**

```bash
git add resources/views/legal/privacy.blade.php
git commit -m "feat: add comprehensive privacy policy content in Indonesian"
```

---

## Task 5: Write Terms of Service Content

**Files:**
- Modify: `resources/views/legal/terms.blade.php`

**Step 1: Replace terms view with complete content**

Replace entire `resources/views/legal/terms.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Syarat dan Ketentuan</h1>
        <p class="text-sm text-gray-500">Terakhir diperbarui: {{ date('d M Y') }}</p>
    </div>

    <div class="prose prose-slate max-w-none">
        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">1. Ketentuan Umum</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Dengan mengakses dan menggunakan sistem pelaporan BELACALL yang dikelola oleh Pemerintah Desa, Anda menyetujui syarat dan ketentuan berikut. Layanan ini dirancang untuk memfasilitasi pelaporan masyarakat terkait permasalahan dan kebutuhan di wilayah desa.
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Penerimaan:</strong> Penggunaan layanan merupakan persetujuan atas seluruh ketentuan ini</li>
                <li><strong>Kelayakan:</strong> Layanan terbuka untuk seluruh warga desa dan pihak yang berkepentingan</li>
                <li><strong>Perubahan:</strong> Kami berhak mengubah ketentuan ini dengan pemberitahuan di halaman ini</li>
                <li><strong>Hukum Berlaku:</strong> Ketentuan ini tunduk pada hukum Republik Indonesia</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">2. Layanan Pelaporan</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                BELACALL menyediakan beberapa cara untuk mengirimkan laporan:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Website:</strong> Formulir pelaporan online di alamat resmi BELACALL</li>
                <li><strong>Telegram Bot:</strong> Interaksi melalui bot Telegram untuk kemudahan akses</li>
                <li><strong>Tracking:</strong> Sistem pelacakan status laporan secara real-time</li>
            </ul>
            <h3 class="text-xl font-medium text-gray-700 mt-6 mb-3">Jenis Laporan yang Diterima:</h3>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li>Infrastruktur (jalan, jembatan, drainase, penerangan)</li>
                <li>Pelayanan publik (kesehatan, pendidikan, administrasi)</li>
                <li>Lingkungan (sampah, polusi, banjir)</li>
                <li>Keamanan dan ketertiban</li>
                <li>Pengaduan dan aspirasi masyarakat</li>
                <li>Laporan darurat dengan prioritas tinggi</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">3. Kewajiban Warga</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Sebagai pengguna layanan, Anda wajib:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Memberikan Informasi Akurat:</strong> Data identitas, lokasi, dan deskripsi laporan harus benar dan dapat diverifikasi</li>
                <li><strong>Tidak Melaporkan Hal Palsu:</strong> Laporan palsu atau menyesatkan dapat dikenakan sanksi sesuai hukum yang berlaku</li>
                <li><strong>Menggunakan Bahasa Sopan:</strong> Hindari bahasa yang mengandung ujaran kebencian, ancaman, atau tidak pantas</li>
                <li><strong>Tidak Menyalahgunakan Sistem:</strong> Larangan spam, flooding, atau tindakan yang mengganggu operasional sistem</li>
                <li><strong>Berkomunikasi Secara Konstruktif:</strong> Merespons permintaan klarifikasi atau revisi dengan kooperatif</li>
                <li><strong>Menjaga Kerahasiaan Magic Link:</strong> Jangan bagikan link autentikasi yang dikirimkan kepada Anda</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">4. Penggunaan Telegram Bot</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Ketentuan khusus untuk penggunaan Telegram Bot:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Pendaftaran:</strong> Anda harus mendaftarkan nomor telepon untuk menggunakan bot</li>
                <li><strong>Interaksi:</strong> Ikuti instruksi bot untuk memastikan laporan diproses dengan benar</li>
                <li><strong>Notifikasi:</strong> Anda akan menerima notifikasi otomatis yang dapat dinonaktifkan</li>
                <li><strong>Respons:</strong> Bot tidak aktif di luar jam kerja kecuali untuk laporan darurat</li>
                <li><strong>Penghentian:</strong> Kami dapat memblokir akses bot jika terjadi pelanggaran ketentuan</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">5. Service Level Agreement (SLA)</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Kami berkomitmen pada standar pelayanan berikut:
            </p>
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <table class="w-full text-sm text-gray-700">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2">Prioritas</th>
                            <th class="text-left py-2">Waktu Respons</th>
                            <th class="text-left py-2">Waktu Penyelesaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-100">
                            <td class="py-2">Darurat</td>
                            <td class="py-2">&lt; 1 jam</td>
                            <td class="py-2">Sesuai situasi</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2">Tinggi</td>
                            <td class="py-2">&lt; 24 jam</td>
                            <td class="py-2">3-5 hari kerja</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2">Normal</td>
                            <td class="py-2">&lt; 48 jam</td>
                            <td class="py-2">7-14 hari kerja</td>
                        </tr>
                        <tr>
                            <td class="py-2">Rendah</td>
                            <td class="py-2">&lt; 72 jam</td>
                            <td class="py-2">14-30 hari kerja</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Eskalasi:</strong> Laporan yang melampaui SLA akan dieskalasi ke pejabat berwenang</li>
                <li><strong>Tracking:</strong> Anda dapat memantau status laporan dan SLA secara real-time</li>
                <li><strong>Luar Biasa:</strong> Waktu penyelesaian dapat berubah sesuai kompleksitas dan ketersediaan sumber daya</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">6. Batasan Tanggung Jawab</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Pemerintah Desa tidak bertanggung jawab atas:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li>Kerugian akibat keterlambatan yang disebabkan faktor di luar kendali (force majeure)</li>
                <li>Kerugian tidak langsung, insidental, atau konsekuensial</li>
                <li>Ketidakakuratan informasi yang diberikan oleh pelapor</li>
                <li>Gangguan layanan akibat pemeliharaan atau perbaikan sistem</li>
                <li>Tindakan pihak ketiga yang mempengaruhi layanan</li>
            </ul>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Layanan ini disediakan "sebagaimana adanya" tanpa jaminan tersurat maupun tersirat.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">7. Penyelesaian Sengketa</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Jika terjadi perselisihan terkait layanan BELACALL:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li><strong>Musyawarah:</strong> Penyelesaian diutamakan melalui musyawarah untuk mufakat</li>
                <li><strong>Pengaduan:</strong> Sampaikan pengaduan melalui kontak resmi yang tersedia</li>
                <li><strong>Mediasi:</strong> Jika tidak tercapai kesepakatan, dapat dimediasi oleh pihak ketiga</li>
                <li><strong>Hukum:</strong> Sengketa yang tidak dapat diselesaikan akan diselesaikan melalui jalur hukum sesuai peraturan perundang-undangan yang berlaku di Indonesia</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">8. Perubahan Syarat dan Ketentuan</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Kami dapat memperbarui syarat dan ketentuan ini sewaktu-waktu. Perubahan akan:
            </p>
            <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                <li>Diumumkan di halaman ini dengan tanggal pembaruan terbaru</li>
                <li>Berlaku efektif sejak tanggal publikasi</li>
                <li>Penggunaan berkelanjutan dianggap sebagai penerimaan atas perubahan</li>
            </ul>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Kami menyarankan Anda meninjau halaman ini secara berkala.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">9. Hukum yang Berlaku</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Syarat dan ketentuan ini diatur dan ditafsirkan sesuai dengan hukum Republik Indonesia. Segala perselisihan yang timbul akan tunduk pada yurisdiksi pengadilan yang berwenang di Indonesia.
            </p>
        </section>

        <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">Kontak</h2>
            <p class="text-base text-gray-600 leading-relaxed mb-4">
                Untuk pertanyaan atau klarifikasi terkait syarat dan ketentuan ini, hubungi:
            </p>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-base text-gray-700 mb-2"><strong>Pemerintah Desa - BELACALL</strong></p>
                <p class="text-sm text-gray-600 mb-1">Email: admin@desa.go.id</p>
                <p class="text-sm text-gray-600 mb-1">Telepon: (xxx) xxx-xxxx</p>
                <p class="text-sm text-gray-600">Alamat: Kantor Desa [Nama Desa]</p>
            </div>
        </section>

        <div class="mt-12 p-6 bg-green-50 rounded-lg border border-green-200">
            <p class="text-sm text-green-800">
                <strong>Persetujuan:</strong> Dengan menggunakan layanan BELACALL, Anda menyatakan telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan di atas.
            </p>
        </div>
    </div>
</div>
@endsection
```

**Step 2: Verify terms page renders**

Run: `php artisan serve`
Visit: `http://localhost:8000/terms`
Expected: Page renders with complete content

**Step 3: Commit**

```bash
git add resources/views/legal/terms.blade.php
git commit -m "feat: add comprehensive terms of service content in Indonesian"
```

---

## Task 6: Test Complete Integration

**Files:**
- Modify: `tests/Feature/LegalPagesTest.php`

**Step 1: Add comprehensive integration tests**

Add to `tests/Feature/LegalPagesTest.php`:

```php
    public function test_privacy_page_displays_required_sections(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertStatus(200);
        $response->assertSee('Kebijakan Privasi');
        $response->assertSee('Pendahuluan');
        $response->assertSee('Informasi yang Kami Kumpulkan');
        $response->assertSee('Integrasi Telegram');
        $response->assertSee('Magic Link Authentication');
        $response->assertSee('Hak Warga');
    }

    public function test_terms_page_displays_required_sections(): void
    {
        $response = $this->get(route('legal.terms'));

        $response->assertStatus(200);
        $response->assertSee('Syarat dan Ketentuan');
        $response->assertSee('Ketentuan Umum');
        $response->assertSee('Layanan Pelaporan');
        $response->assertSee('Kewajiban Warga');
        $response->assertSee('Service Level Agreement');
    }

    public function test_legal_pages_are_publicly_accessible(): void
    {
        // Privacy page
        $privacyResponse = $this->get(route('legal.privacy'));
        $privacyResponse->assertStatus(200);

        // Terms page
        $termsResponse = $this->get(route('legal.terms'));
        $termsResponse->assertStatus(200);
    }
```

**Step 2: Run all tests**

Run: `php artisan test tests/Feature/LegalPagesTest.php`
Expected: All 6 tests PASS

**Step 3: Commit**

```bash
git add tests/Feature/LegalPagesTest.php
git commit -m "test: add comprehensive integration tests for legal pages"
```

---

## Task 7: Final Verification

**Step 1: Run full test suite**

Run: `php artisan test`
Expected: All tests PASS

**Step 2: Manual visual verification**

Run: `php artisan serve`
Visit: `http://localhost:8000/privacy`
Visit: `http://localhost:8000/terms`
Check: Footer links on home page work correctly

**Step 3: Verify accessibility as Warga (no auth)**

- Ensure both pages load without login
- Verify footer links appear on all pages using the layout
- Test on mobile viewport

**Final Commit:**

```bash
git add docs/plans/2025-02-15-privacy-terms-pages-design.md docs/plans/2025-02-15-privacy-terms-implementation.md
git commit -m "docs: add design and implementation plan for privacy and terms pages"
```

---

## Summary

**Files Created:**
- `app/Http/Controllers/LegalController.php`
- `resources/views/legal/privacy.blade.php`
- `resources/views/legal/terms.blade.php`
- `tests/Feature/LegalPagesTest.php`

**Files Modified:**
- `routes/web.php` - Added two routes
- `resources/views/layouts/app.blade.php` - Updated footer links

**Total Commits:** 7

**Test Coverage:**
- Route accessibility tests
- View rendering tests
- Footer link tests
- Content section tests
- Public access tests

**Key Features:**
- Comprehensive Indonesian legal content
- Covers standard reporting, Telegram integration, magic link auth, SLA
- Publicly accessible (no authentication required)
- Responsive design matching existing app style
- SEO-friendly clean URLs
