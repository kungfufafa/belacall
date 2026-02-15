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
