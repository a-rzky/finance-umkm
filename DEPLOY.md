# Deploy ke Railway

## 1. Siapkan repo

Railway men-deploy dari repo Git. Project ini belum berupa repo:

```bash
git init
git add .
git commit -m "feat: aplikasi pencatatan transaksi UMKM"
git branch -M main
git remote add origin <url-repo-kamu>
git push -u origin main
```

Pastikan `.env` **tidak** ikut ter-commit (sudah masuk `.gitignore`).

## 2. Buat service di Railway

1. **New Project → Deploy from GitHub repo**, pilih repo ini.
2. **New → Database → Add PostgreSQL** di project yang sama.

## 3. Isi variabel environment

Di service aplikasi, tab **Variables**:

| Variabel | Nilai |
| --- | --- |
| `APP_NAME` | `Catatan Usaha` |
| `APP_ENV` | `production` |
| `APP_KEY` | hasil `php artisan key:generate --show` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://<domain-railway-kamu>` |
| `APP_TIMEZONE` | `Asia/Jakarta` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | `${{Postgres.DATABASE_URL}}` |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `CACHE_STORE` | `database` |
| `LOG_CHANNEL` | `stderr` |

Catatan penting:

- **`APP_KEY` wajib diisi.** Tanpa itu semua session dan cookie terenkripsi gagal.
- **`APP_DEBUG` harus `false`.** Kalau `true`, stack trace berisi kredensial akan tampil ke pengunjung.
- `DB_URL` memakai referensi `${{Postgres.DATABASE_URL}}` — Railway mengisinya otomatis, jadi `DB_HOST`/`DB_PASSWORD` tidak perlu diisi.
- `SESSION_DRIVER`, `QUEUE_CONNECTION`, dan `CACHE_STORE` harus `database`. Filesystem Railway bersifat sementara dan terhapus setiap redeploy.
- `LOG_CHANNEL=stderr` supaya log muncul di panel Railway, bukan ditulis ke disk yang akan hilang.

## 4. Jalankan migrasi tiap deploy

Di **Settings → Deploy → Pre-Deploy Command**:

```
php artisan migrate --force
```

Ditaruh di pre-deploy (bukan di start command) supaya migrasi berjalan sekali
per deploy, bukan berulang tiap container restart.

## 5. Setelah deploy

Buka domain Railway, lalu buat toko pertama lewat `/daftar`.

Untuk memastikan PWA aktif: buka di Chrome Android, harus muncul tawaran
"Tambahkan ke layar utama". PWA hanya jalan di HTTPS — domain Railway sudah
HTTPS, jadi tidak ada yang perlu diatur.

---

## Yang belum terverifikasi

Konfigurasi di atas disusun berdasarkan cara Nixpacks dan Railway bekerja, tapi
**belum pernah dijalankan di Railway sungguhan** dari sesi ini. Yang sudah
terbukti jalan adalah aplikasinya sendiri di lingkungan lokal dengan PostgreSQL.

Kalau build gagal, yang paling mungkin jadi penyebab:

- Versi PHP yang dipilih Nixpacks lebih rendah dari `^8.2`. Perbaikannya: tambah
  variabel `NIXPACKS_PHP_VERSION=8.4`.
- `npm ci` gagal karena `package-lock.json` tidak ikut ter-commit. Pastikan file
  itu ada di repo.
