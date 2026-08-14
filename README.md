# Catatan Usaha

Aplikasi pencatatan transaksi sederhana untuk UMKM. Catat uang masuk dan uang
keluar lewat layar bergaya mesin kasir, lalu lihat rekapnya per hari.

Multi-tenant: satu akun = satu usaha, datanya terpisah penuh dari usaha lain.
Berupa PWA, jadi bisa dipasang di layar utama HP.

## Tumpukan teknologi

- Laravel 13 (PHP 8.4)
- Inertia.js + Vue 3 (Composition API)
- Tailwind CSS 4
- PostgreSQL
- PWA: manifest + service worker

## Menjalankan di lokal

Prasyarat: PHP 8.4, Composer, Node 20+, PostgreSQL.

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

createdb simple_finance_umkm
# sesuaikan DB_USERNAME / DB_PASSWORD di .env
php artisan migrate

npm run build       # atau: npm run dev
php artisan serve
```

Buka `http://localhost:8000`, lalu buat toko lewat `/daftar` — cukup nama toko,
nama pengguna, dan kata sandi, langsung masuk ke dashboard.

## Menjalankan tes

```bash
createdb simple_finance_umkm_test    # cukup sekali
php artisan test
```

Tes sengaja berjalan di PostgreSQL, bukan sqlite bawaan Laravel, karena query
rekap memakai `GROUP BY` alias dan `CASE WHEN` yang perilakunya berbeda antar
engine database.

## Struktur

```
app/
├── Enums/TransactionType.php        masuk | keluar
├── Http/
│   ├── Controllers/                 tipis, hanya menerima & mengembalikan respons
│   └── Requests/                    seluruh validasi input
├── Models/
│   ├── Concerns/BelongsToTenant.php isi tenant_id otomatis
│   └── Scopes/TenantScope.php       saring query per tenant
└── Services/                        seluruh logika bisnis

resources/js/
├── Layouts/AppLayout.vue            header, navigasi bawah, notifikasi
├── Pages/
│   ├── Kasir.vue                    numpad, layar utama
│   ├── Riwayat.vue                  daftar per hari, ubah & hapus
│   ├── Rekap.vue                    rekap harian & per kategori
│   └── Auth/                        masuk & daftar
└── pwa.js                           service worker & tawaran pasang
```

## Keputusan rancangan

**Pendaftaran hanya tiga isian dan tidak menyimpan email.** Pemilik warung
mengisinya sambil berdiri di depan lapak, jadi setiap isian tambahan berarti
calon pengguna yang berhenti di tengah jalan. Konsekuensinya nyata dan disengaja:
**belum ada jalur lupa kata sandi** — pemulihan hanya bisa lewat database.
Menambahkannya nanti butuh satu migration untuk kolom email.

**`occurred_on` bertipe DATE, bukan datetime.** Rekap harian dengan timestamp
UTC membuat transaksi jam 8 malam WIB tercatat di tanggal berikutnya. Menyimpan
tanggal murni menghilangkan masalah itu sepenuhnya.

**`amount` bertipe integer, bukan decimal.** Rupiah tidak mengenal sen, dan
integer bebas dari galat pembulatan bilangan pecahan.

**`tenant_id` tidak pernah `fillable`.** Nilainya hanya diisi `BelongsToTenant`
dari pengguna yang sedang login, sehingga `tenant_id` yang dititipkan lewat body
request diabaikan begitu saja.

**`TenantScope` bersifat fail-closed.** Tanpa pengguna yang login, query
sengaja dikosongkan. Route yang lupa dipasangi middleware `auth` menjadi tidak
berguna alih-alih membocorkan data usaha lain.

**Service worker tidak pernah menyimpan halaman hasil login.** Satu HP sering
dipakai bergantian; halaman tersimpan bisa tersaji ke akun lain setelah keluar.
Yang di-cache hanya aset statis.

## Deploy

Lihat [DEPLOY.md](DEPLOY.md).

## Ikon

Ikon PWA dibuat oleh skrip tanpa dependency:

```bash
python3 tools/generate-icons.py
```
