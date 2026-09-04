# Jadwal Kuliah FT

Sistem penjadwalan kuliah Fakultas Teknik Universitas Riau.

## Kebutuhan

- PHP 8.2 atau lebih baru
- Composer
- Node.js 18 atau lebih baru
- NPM
- MySQL atau MariaDB
- Git

## Setup Awal

Clone repository:

```bash
git clone https://gitlab.com/unri-3/jadwal-kuliah-ft.git
cd jadwal-kuliah-ft
```

Install dependency:

```bash
composer install
npm install
```

Buat file environment:

```bash
cp .env.example .env
```

Di Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

## Konfigurasi Database

Buat database MySQL/MariaDB, misalnya `penjadwalan_perkuliahan`, lalu sesuaikan `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=penjadwalan_perkuliahan
DB_USERNAME=root
DB_PASSWORD=
```

Import data awal dari dump yang masih digunakan:

```bash
mysql -u root -p penjadwalan_perkuliahan < penjadwalan_perkuliahan.sql
```

Setelah import, jalankan migration terbaru dan generate ulang slot jadwal per jurusan:

```bash
php artisan migrate
php artisan schedule:regenerate-jurusan-slots --force
php artisan optimize:clear
```

Perintah `schedule:regenerate-jurusan-slots` akan membersihkan slot/jadwal lama yang tidak valid, lalu membuat relasi slot berdasarkan kebutuhan SKS masing-masing jurusan.

## Menjalankan Aplikasi

Cara paling ringkas untuk development:

```bash
composer run dev
```

Command tersebut menjalankan backend Laravel, queue listener, dan Vite dev server.

Jika ingin menjalankan manual, gunakan dua terminal:

```bash
php artisan serve
```

```bash
npm run dev
```

Frontend Vite berjalan di `http://localhost:5173`, sedangkan backend Laravel biasanya di `http://localhost:8000`.

## Build Production

```bash
npm run build
```

## Akun Default

Jika memakai dump SQL atau seeder default:

```text
Email: admin@unri.ac.id
Password: admin123
```

## Struktur Penting

- `app/Modules` - kode utama aplikasi berbasis modul.
- `resources/js` - frontend React.
- `routes/api.php` - route API utama.
- `database/migrations` - migration database.
- `database/seeders` - seeder data dasar.
- `app/Modules/Penjadwalan` - modul penjadwalan.
- `app/Modules/Resource` - modul ruangan, hari, waktu, dan slot.


## Command Penting

Regenerasi slot jadwal per jurusan:

```bash
php artisan schedule:regenerate-jurusan-slots --force
```

Reset password user:

```bash
php artisan user:reset-password email@example.com password_baru
```

Clear cache config dan route:

```bash
php artisan optimize:clear
```

## Testing

Jalankan seluruh test:

```bash
php artisan test
```

## Troubleshooting

Jika package frontend tidak ditemukan:

```bash
npm install
npm run build
```

Jika class PHP tidak ditemukan:

```bash
composer dump-autoload
php artisan optimize:clear
```

Jika CORS atau config masih memakai nilai lama:

```bash
php artisan optimize:clear
```

Jika database kosong setelah `migrate`, gunakan dump `penjadwalan-perkuliahan.sql` untuk data awal lengkap. Seeder bawaan hanya menyiapkan data minimum seperti user default.
