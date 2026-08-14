# Deploy ke Railway

Aplikasi ini sudah berjalan di Railway:
**https://finance-umkm-production.up.railway.app**

Deploy berlangsung otomatis setiap ada push ke branch `main`.

## Susunan di Railway

Project `Finance-UMKM` berisi dua service:

| Service | Isi |
| --- | --- |
| `finance-umkm` | aplikasi, sumbernya repo GitHub `a-rzky/finance-umkm` |
| `Postgres` | database, image `postgres-ssl:18` dengan volume 5 GB |

Builder yang dipakai Railway adalah **Railpack** (bukan Nixpacks). Railpack
sudah menangani Laravel dan Vite tanpa konfigurasi tambahan: `composer install`
dan `npm run build` berjalan sendiri, dan berkas hasil build tersaji di
`/build/assets/`.

## Variabel environment

Sudah terpasang di service `finance-umkm`:

| Variabel | Nilai |
| --- | --- |
| `APP_NAME` | `Catatan Usaha` |
| `APP_ENV` | `production` |
| `APP_KEY` | kunci base64 |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://finance-umkm-production.up.railway.app` |
| `APP_TIMEZONE` | `Asia/Jakarta` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | `${{Postgres.DATABASE_URL}}` |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `CACHE_STORE` | `database` |
| `LOG_CHANNEL` | `stderr` |

Alasan beberapa pilihan:

- `DB_URL` memakai referensi antar-service, jadi kredensial database tidak
  pernah disalin dan tetap benar meski Railway memutarnya.
- `SESSION_DRIVER`, `QUEUE_CONNECTION`, dan `CACHE_STORE` wajib `database`
  karena filesystem Railway terhapus setiap redeploy.
- `LOG_CHANNEL=stderr` supaya log tampil di panel Railway, bukan ditulis ke
  disk yang akan hilang.

## Migrasi

Dijalankan otomatis lewat `railway.json`:

```json
"preDeployCommand": ["php artisan migrate --force"]
```

Ditaruh di pre-deploy agar berjalan sekali per deploy, bukan berulang setiap
container restart. Disimpan di repo, bukan sebagai setelan dashboard, supaya
perubahannya ikut terekam di riwayat Git.

## PHP 8.4 itu wajib

`composer.json` menyatakan `"php": "^8.4"`. Angka ini bukan preferensi:
Laravel 13 dan Symfony 8 yang ter-lock menuntut PHP >= 8.4.1. Deploy pertama
gagal justru karena berkas ini sempat menyebut `^8.3`, sehingga builder dengan
patuh memasang PHP 8.3.33 lalu `composer install` menolak berjalan.

Kalau suatu saat build gagal dengan keluhan versi PHP, periksa baris itu lebih
dulu sebelum menyentuh setelan builder.

## Perintah yang sering dipakai

```bash
railway link -p Finance-UMKM -e production   # sambungkan folder ini
railway service list                          # status semua service
railway logs                                  # log aplikasi
railway logs --build                          # log build
railway variables --service finance-umkm      # lihat variabel
railway redeploy                              # deploy ulang tanpa push
```

## Yang sudah terverifikasi

Diperiksa langsung terhadap domain produksi setelah deploy berhasil:

- `/` mengalihkan ke `/masuk` memakai **https** — pengenalan proxy Railway benar
- Aset Vite ter-build dan tersaji (`app-*.js` 216 KB, `app-*.css` 46 KB)
- Migrasi berjalan: percobaan masuk memberi respons validasi, bukan galat 500
- Session tersimpan di database (cookie `catatan-usaha-session` terbentuk)
- `APP_DEBUG=false` benar-benar berlaku: halaman galat tidak membocorkan
  stack trace maupun jalur berkas server
- Berkas PWA tersaji dengan tipe konten yang benar: `manifest.webmanifest`,
  `sw.js`, `offline.html`, dan ikon
