# Sewa Garasi

Aplikasi web penyewaan garasi kendaraan (sepeda/motor, mobil bensin, mobil listrik) dengan dua role: **Admin** dan **Penyewa**.
Dibangun dengan Laravel (arsitektur MVC + service layer) dan basis data relasional MariaDB/MySQL.

## Fitur

**Penyewa**
- Mendaftar (NIK, nama, telepon, email sebagai username, password). Akun menunggu verifikasi admin.
- Sewa garasi baru:
  - detail kendaraan (tipe, plat kecuali sepeda, merek/tipe, kapasitas listrik dan sisa listrik saat ini untuk mobil listrik);
  - sewa harian atau bulanan; tanggal mulai default hari ini, boleh tanggal mendatang (tidak boleh tanggal lewat);
  - tanggal selesai dihitung otomatis;
  - daftar garasi yang tersedia sesuai tipe kendaraan;
  - total biaya dihitung sistem;
  - upload bukti pelunasan.
- Melihat riwayat sewa beserta bukti pembayaran.

**Admin**
- Membuat garasi secara berkelompok dengan kode otomatis (S001, C001, E001, melanjutkan nomor terakhir; maks. 999).
- Mengubah harga garasi (harga harian, dan harga per kWh untuk garasi mobil listrik). Sewa yang sudah ada tetap memakai harga lamanya.
- Menghapus garasi yang belum pernah disewa. Garasi yang punya riwayat sewa tidak bisa dihapus.
- Verifikasi pendaftar baru: memeriksa data KTP secara manual, lalu menerima atau menolak.
- Perpanjang sewa:
  1. daftar garasi yang disewa hari ini;
  2. ubah tanggal selesai;
  3. sistem menghitung sisa biaya;
  4. upload bukti bayar.

  Penyewa mengajukan perpanjangan di luar sistem, misalnya lewat WhatsApp.
- Laporan sewa: per bulan dalam tahun tertentu, per hari dalam bulan tertentu, per tipe garasi, per penyewa (dengan grafik).

## Aturan bisnis utama

| Aturan | Implementasi |
|---|---|
| Penempatan kendaraan | Sepeda/motor → garasi sepeda (S). Mobil → garasi mobil (C). Mobil listrik → garasi mobil (C) atau garasi mobil listrik (E). |
| Tanggal mulai | Default hari ini; penyewa boleh memilih tanggal mendatang, tidak boleh tanggal yang sudah lewat (`after_or_equal:today` + dicek ulang di RentalService). Status sewa: Akan datang / Aktif / Selesai. |
| Sewa bulanan | 1 bulan; jumlah hari = jumlah hari di bulan tanggal mulai (Feb 28/29, Apr 30, Mei 31). Harga = **25 × harga harian**. |
| Sewa harian | 1 s/d (jumlah hari bulan mulai − 1) hari, dan tidak lebih dari 30 hari. |
| Tanggal selesai | Dihitung inklusif: mulai + durasi − 1. |
| Isi listrik | Mobil listrik di garasi mobil listrik wajib isi sampai penuh: (kapasitas − sisa listrik saat ini) kWh × harga per kWh. Sisa listrik 0 sampai kapasitas. |
| Total sewa baru | Biaya sewa + biaya isi listrik. |
| Perpanjangan (admin) | Setiap bulan kalender penuh pada hari tambahan = 25 × harian, sisa hari = harian. Sisa biaya selalu lebih dari 0. |
| Ketersediaan | Satu garasi hanya untuk satu kendaraan pada tanggal yang sama (cek tabrakan rentang tanggal). |

## Teknologi dan library pihak ketiga

| Library / komponen | Versi | Lisensi | Kegunaan | Dokumentasi |
|---|---|---|---|---|
| Laravel Framework | 13.43 | MIT | Framework MVC: routing, Eloquent ORM, Blade, validasi, autentikasi | https://laravel.com/docs |
| Carbon | 3.15 | MIT | Perhitungan tanggal (jumlah hari per bulan, tanggal selesai) | https://carbon.nesbot.com/docs |
| Tailwind CSS | 4.3 | MIT | Styling tampilan | https://tailwindcss.com/docs |
| Vite + laravel-vite-plugin | 7.3 / 2.1 | MIT | Build aset CSS/JS | https://vite.dev/guide |
| Chart.js (CDN jsDelivr) | 4.4.1 | MIT | Grafik pada halaman laporan | https://www.chartjs.org/docs |
| PHPUnit | 12.5 | BSD-3-Clause | Unit test | https://docs.phpunit.de |
| Laravel Pint | 1.32 | MIT | Formatter / coding standard PHP | https://laravel.com/docs/pint |
| MariaDB (XAMPP) | 10.4 | GPL-2.0 | Basis data relasional | https://mariadb.com/kb |

## Kebutuhan

- PHP 8.3+ (ekstensi: pdo_mysql, mbstring, fileinfo, gd)
- Composer 2
- Node.js 20+ dan npm
- MariaDB/MySQL (mis. XAMPP), dengan database kosong bernama `sewa_garasi` (charset `utf8mb4`)

## Cara menjalankan

```bash
composer install
npm install
npm run build
copy .env.example .env          # Linux/macOS: cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
php artisan serve
```

Buka http://127.0.0.1:8000.

Pengaturan database ada di `.env`. Nilai default sudah sesuai XAMPP:
- `DB_HOST=127.0.0.1`, `DB_PORT=3306`
- `DB_DATABASE=sewa_garasi`
- `DB_USERNAME=root`, `DB_PASSWORD=` (kosong)

`php artisan migrate:fresh --seed` mengembalikan data ke kondisi demo awal.

### Akun demo (password semua: `password`)

| Email | Role | Status |
|---|---|---|
| admin1@sewagarasi.test | Admin | – |
| renter1@sewagarasi.test | Penyewa | Diterima (punya motor dan mobil listrik) |
| renter2@sewagarasi.test | Penyewa | Diterima (punya mobil dan sepeda) |
| renter3@sewagarasi.test, renter4@sewagarasi.test | Penyewa | Menunggu verifikasi |
| renter5@sewagarasi.test | Penyewa | Ditolak |

## Menjalankan test

```bash
php artisan test
```

Unit test (white-box) ada di `tests/Unit` (50 test) dan menguji logika bisnis secara langsung, tanpa database:
- `RentalPeriodServiceTest`: durasi sewa bulanan/harian dan tanggal selesai, termasuk nilai batas.
- `PricingServiceTest`: biaya sewa, biaya isi listrik (kapasitas − sisa listrik), total sewa baru, dan sisa biaya perpanjangan.
- `GarageCodeGeneratorTest`: kode garasi otomatis (S/C/E + 3 digit, maksimal 999).
- `VehicleTypeTest`: aturan penempatan kendaraan ke tipe garasi.

## Struktur kode (MVC + service layer)

| Folder | Isi |
|---|---|
| `app/Models` | **Model**: User, Garage, Vehicle, Rental, RentalExtension (Eloquent) |
| `resources/views` | **View**: Blade (auth, tenant/rentals, admin/garages, admin/tenants, admin/extensions, admin/reports) |
| `app/Http/Controllers` | **Controller**: Auth, Tenant\RentalController, Admin\* |
| `app/Services` | Logika bisnis: PricingService, RentalPeriodService, GarageCodeGenerator, GarageService, RentalService, ReportService |
| `app/Http/Requests` | Validasi input (Form Request) |
| `app/Enums` | GarageType, VehicleType, RentalType, UserRole, UserStatus, GarageStatus |
| `app/Http/Middleware/EnsureRole.php` | Pembatasan akses berdasarkan role |
| `database/migrations` | Skema tabel, termasuk foreign key dan aksi referensial (RESTRICT / CASCADE) |
| `database/seeders` | Data demo |
