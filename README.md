# DonasiKu — Platform Crowdfunding

## Persiapan Database

Import **satu-satunya** skema resmi:

```sql
mysql -u root -p donasiku_db < donasiku.sql
```

File `donasiku.sql` di root sudah mencakup semua tabel, data seed, dan relasi.  
File `database/crowdfunding.sql` (stub lama) **telah dihapus** — tidak digunakan.

## Konfigurasi Koneksi

Edit `koneksi.php` di root:

```php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'donasiku_db';
```

## Struktur Folder

```
/
├── koneksi.php          # Koneksi DB + helper functions
├── donasiku.sql         # Skema resmi (satu-satunya)
├── pages/               # Semua halaman PHP
│   ├── login.php
│   ├── main.php
│   ├── detailDonasi.php
│   ├── detail_kelola.php
│   ├── kelola_kampanye.php
│   └── ...
├── assets/              # Gambar statis
├── uploads/
│   ├── bukti/           # Upload bukti transfer donatur
│   └── kampanye/        # Upload foto kampanye
└── style/
    └── global.css
```

## Akun Demo

| Role      | Username    | Password |
|-----------|-------------|----------|
| Pengelola | pengelola1  | password |
| Donatur   | donatur1    | password |
