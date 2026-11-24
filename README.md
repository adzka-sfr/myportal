# ATARA

Portal aplikasi ATARA berbasis Laravel 12 dengan satu kali login, akses per pengguna, dan fitur yang dapat dibatasi oleh Superadmin.

## Isi portal

- Kalkulator listrik: estimasi kWh dan biaya satu atau beberapa alat.
- Kalkulator diskon: diskon tunggal dan diskon bertingkat.
- Checklist belanja: item, jumlah, status dibeli, dan beberapa daftar pribadi.
- Kalkulator bensin: kebutuhan BBM, biaya perjalanan, dan perbandingan kendaraan.
- Panel Superadmin: membuat/mengubah akun dan mengatur aplikasi serta fitur yang boleh dipakai.

Tidak ada pendaftaran publik. Akun dibuat oleh Superadmin. Setiap fitur diperiksa di server; menyembunyikan tombol saja tidak menjadi mekanisme pembatas akses.

## Menjalankan secara lokal

Persyaratan: PHP 8.2+, Composer, dan ekstensi PHP yang dibutuhkan Laravel (termasuk OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, Fileinfo, dan cURL).

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan portal:make-superadmin
php artisan serve
```

Buka `http://127.0.0.1:8000` dan masuk memakai akun Superadmin yang baru dibuat. Perintah tersebut meminta nama, email, dan kata sandi tanpa menyimpan kredensial contoh di source control.

## Menyiapkan GitHub

`vendor`, `.env`, dan file hasil build lokal tidak dimasukkan ke Git. Commit `composer.lock` agar versi dependency yang dipasang dapat diulang. Simpan hanya `.env.example` sebagai panduan variabel.

```sh
git add .
git commit -m "Build Ruang Alat portal"
git push
```

## Deploy ke Hostinger Web/Cloud

Pastikan domain memakai PHP 8.2 atau lebih baru dan buat database MySQL di hPanel. Isi konfigurasi produksi di `.env` pada server (jangan commit ke GitHub):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST=host-database-dari-hPanel
DB_PORT=3306
DB_DATABASE=nama-database
DB_USERNAME=user-database
DB_PASSWORD=password-database
```

Buat `APP_KEY` untuk instalasi produksi dengan `php artisan key:generate` di folder aplikasi server. Jangan memakai ulang `APP_KEY` lokal atau membagikannya.

**Document root harus menunjuk hanya ke folder `public/` Laravel.** Simpan kode aplikasi di luar `public_html`; letakkan isi folder `public/` di web root atau arahkan document root ke folder `public/` jika paketmu menyediakan pengaturan itu. Jangan menaruh seluruh root repository Laravel di `public_html`.

Setelah source tersedia di folder aplikasi melalui SSH/Git, jalankan dari folder itu:

```sh
composer2 install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan portal:make-superadmin
```

Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh PHP. Setelah domain aktif dan HTTPS siap, login sebagai Superadmin, buat akun pengguna, lalu pilih izin aplikasi dan fiturnya.

## Keamanan deployment

- `.env` produksi hanya berada di server dan harus tetap di luar GitHub.
- Pastikan `APP_DEBUG=false` di produksi.
- Jangan mengunggah direktori `vendor` atau database lokal.
- Sebelum menyalakan auto-deploy Hostinger, pastikan target deployment tidak membuka file selain isi folder `public/` ke web.
