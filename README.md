<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Stability Testing (k6)

Project ini sudah punya script load test yang bisa dipakai untuk local, staging, atau production cukup dengan ganti URL target.

### Lokasi Script

- `tests/Load/stability.k6.js`
- `tests/Load/run-stability.sh`

### Jalankan Cepat

```bash
# Pastikan local aman (tidak kirim pesan ke pihak ketiga)
# TELEGRAM_FAKE_MODE=true

# Local (smoke test)
./tests/Load/run-stability.sh http://127.0.0.1:8000 smoke

# Staging (baseline)
./tests/Load/run-stability.sh https://staging.example.com baseline

# Production (stress)
./tests/Load/run-stability.sh https://example.com stress
```

### Profil Beban

- `smoke`: validasi cepat endpoint utama.
- `baseline`: simulasi beban normal sebelum launch.
- `stress`: dorong beban tinggi untuk lihat bottleneck.
- `soak`: durasi panjang untuk deteksi memory leak/degradasi.

### Contoh Override (Opsional)

```bash
./tests/Load/run-stability.sh https://staging.example.com baseline \
  -e WEBHOOK_TOKEN=your_token \
  -e BASE_PATH=/app \
  -e HEALTH_RPS=15 \
  -e REPORT_RPS=6 \
  -e TRACKING_RPS=4 \
  -e WEBHOOK_RPS=15 \
  -e SUMMARY_JSON=/tmp/k6-summary.json
```

### Env Penting

- `BASE_URL`: domain target test.
- `K6_PROFILE`: `smoke|baseline|stress|soak`.
- `WEBHOOK_TOKEN`: token untuk endpoint webhook jika aktif.
- `TELEGRAM_FAKE_MODE`: set `true` untuk local agar request ke Telegram tidak dikirim keluar (aman dari spam/ban).
- `ENABLE_HEALTH_SCENARIO`, `ENABLE_REPORT_SCENARIO`, `ENABLE_TRACKING_SCENARIO`, `ENABLE_WEBHOOK_SCENARIO`: nyalakan/matikan skenario.
- `HEALTH_RPS`, `REPORT_RPS`, `TRACKING_RPS`, `WEBHOOK_RPS`: override rate per skenario.
- `K6_DURATION`, `PREALLOCATED_VUS`, `MAX_VUS`: tuning durasi dan virtual users.
- `SUMMARY_JSON`: simpan hasil lengkap run ke file JSON.

### Rekomendasi Urutan Uji

1. `smoke` di local/staging.
2. `baseline` di staging.
3. `stress` di staging dengan data mirip production.
4. `soak` sebelum go-live jika waktu memungkinkan.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
