# Audit Fondasi RBAC Tahap 1

**Project:** SunTrack
**Tanggal audit:** 29 Agustus 2026
**Cakupan:** Dasar implementasi Tahap 1-3 pada `docs/RBAC.md`

## 1. Snapshot Sistem

- Aplikasi menggunakan Laravel 13, PHP 8.3, Sanctum, dan Spatie Laravel Permission.
- Guard autentikasi aktif adalah `web`. Seeder lama membuat role dan permission untuk guard `web` dan `api`.
- Database PostgreSQL telah menjalankan seluruh migrasi sampai migrasi username tanggal 29 Agustus 2026.
- `users.company_id` masih menjadi sumber utama pembatasan data.
- Route admin umumnya hanya memakai `auth:sanctum`. Middleware permission baru digunakan pada System Settings.
- Frontend hanya memeriksa status login. Route, menu, dan tombol belum memakai permission efektif.

Snapshot database sebelum perubahan:

| Data | Kondisi |
|---|---|
| Nama role | `Super Admin`, `Brand Manager`, `Finance Auditor`, `Operational Staff`, `Company` |
| Guard role | Setiap nama tersedia pada `web` dan `api` |
| User aktif berdasarkan type | 1 `admin`, 1 `company` |
| Assignment role tersimpan | 1 `Super Admin`, 2 `Company` pada guard `web` |
| Role `Admin` | Belum dibuat seeder lama |
| Role `Tim` | Belum tersedia |

Jumlah assignment role dapat mencakup user yang sudah soft delete. Migrasi user pada Tahap 6 harus menggunakan daftar user aktif dan user soft delete secara terpisah.

## 2. Temuan Kode

### Role dan Permission

- `database/seeders/RolePermissionSeeder.php` masih membuat lima role lama.
- Daftar permission lama belum mencakup User, Company, Brand, Task, Activity Log, access control, dan system monitoring secara lengkap.
- `app/Http/Controllers/Api/V1/UserController.php` sudah mencoba menetapkan role `Admin`, tetapi role tersebut tidak dibuat oleh seeder lama.
- `app/Http/Resources/UserResource.php` sudah memakai `getAllPermissions()`.
- `app/Http/Controllers/Api/V1/AuthController.php` masih mengirim direct permission melalui relasi `permissions` pada endpoint profil.

### Cakupan Data

Pemeriksaan `Super Admin` dan `users.company_id` tersebar pada:

- `UserController`, `BrandController`, `CampaignController`, `PromotionController`, `TaskController`, `ProductController`, `VariantController`, dan `PromotionVariantController`.
- `PricingAnalyticsController`, `ReportController`, dan `SearchController`.
- `UserRepository`, `BrandRepository`, `CampaignRepository`, `PromotionRepository`, `ProductRepository`, `PricingAnalyticsRepository`, dan `AnalyticsRepository`.

Logika tersebut tetap dipertahankan pada Tahap 1-3. Penggantian dengan layanan cakupan terpusat dilakukan mulai Tahap 4.

### Pengelolaan User

- Field legacy `users.type` masih menerima `admin` dan `company`.
- Form user frontend masih menawarkan tipe Admin dan Company.
- `StoreUserRequest` mewajibkan satu `company_id`.
- `UpdateUserRequest` dan `UserController` belum menerapkan batas perubahan berdasarkan tiga role final.
- Endpoint User masih dilindungi autentikasi tanpa middleware permission.

### Frontend

- `resources/js/stores/auth.js` menyimpan payload user tanpa helper permission.
- `resources/js/router.js` hanya memakai `requiresAuth`, `guestOnly`, dan `public`.
- `resources/js/layouts/AdminLayout.vue` belum menyaring menu berdasarkan permission.
- `resources/js/pages/Users.vue` dan `resources/js/components/UserForm.vue` masih memakai `type` legacy.

## 3. Pemetaan Role Lama

| Role lama | Target | Aturan migrasi pada Tahap 6 |
|---|---|---|
| `Super Admin` | `Super Admin` | Pertahankan; pastikan minimal satu user aktif |
| `Brand Manager` | `Tim` | Pertahankan permission lama yang termasuk whitelist Tim; tetapkan Company dan Brand secara eksplisit |
| `Finance Auditor` | `Tim` | Petakan akses baca, Activity Log, dan Export; `promotion.approve` tidak dibawa karena berada di luar whitelist Tim |
| `Operational Staff` | `Tim` | Pertahankan permission lama yang termasuk whitelist Tim; tetapkan Company dan Brand secara eksplisit |
| `Company` | Bukan user internal | Gunakan secure link; akun lama dinonaktifkan setelah kebutuhan aksesnya dikonfirmasi |

Role `Admin` baru hanya ditetapkan oleh Super Admin sesuai spesifikasi. Seeder Tahap 2 tidak mengubah role user lama selain mempertahankan penetapan Super Admin untuk akun default.

## 4. Titik Perubahan Tahap Berikutnya

| Area | Tahap |
|---|---:|
| Relasi model User, Company, dan Brand assignment | 4 |
| Layanan cakupan dan query scope terpusat | 4 |
| Middleware, Policy, dan validasi entity | 5 |
| Migrasi role, type, permission, dan assignment user lama | 6 |
| Payload autentikasi dengan permission efektif | 7 |
| Halaman User dan Hak Akses | 8 |
| Menu dan tombol berbasis permission | 9 |
| Activity Log perubahan akses dan invalidasi cache | 10 |

## 5. Batas Tahap 1-3

- Role lama tidak dihapus.
- User lama tidak diubah.
- Seeder tidak dijalankan pada database aktif pada tahap ini.
- `users.company_id` tidak dihapus atau diubah.
- Proteksi route dan cakupan modul belum diubah.
- Tabel assignment ditambahkan dalam migrasi terpisah dan dapat di-rollback.

## 6. Audit Ulang Tahap 1-12 dan Penutupan Tahap 13

**Tanggal verifikasi:** 3 September 2026
**Cakupan:** Dokumen, kode backend/frontend, test suite, migrasi, dan database PostgreSQL aktif

Hasil audit ulang:

- Baseline sebelum kontraksi lulus dengan 60 tes dan 332 assertion.
- Tidak ada user aktif maupun soft-deleted yang masih memiliki assignment role legacy.
- Satu Super Admin aktif tetap tersedia.
- Seluruh migrasi Tahap 1-12 berstatus selesai pada database aktif.
- Proteksi permission, Policy, scope query, assignment Company/Brand, payload autentikasi, frontend guard, audit akses, dan invalidasi cache tetap tersedia.
- Dua ketergantungan transisi ditemukan: fallback `users.company_id` pada layanan scope serta filter Company tunggal pada Search dan Pricing Analytics Admin.

Penyesuaian Tahap 13:

- Fallback dan kontrak User `company_id` dihapus dari model, request, resource, controller, factory, seeder, dan frontend.
- Hitungan user Company dipindahkan ke `user_company_assignments`.
- Search dan Pricing Analytics Super Admin/Admin memakai cakupan global.
- Referensi kolom harga Analytics disesuaikan ke `promotion_variant.discount_price`, sesuai skema aktif.
- Role non-final dan kolom `users.company_id` dihapus melalui dua migrasi terpisah dengan snapshot rollback.
- Command serta tes kompatibilitas migrasi legacy dihapus.

Hasil akhir database aktif:

| Pemeriksaan | Hasil |
|---|---:|
| Nama role final | 3 |
| Baris role lintas guard | 6 |
| Role non-final | 0 |
| Assignment role non-final | 0 |
| Super Admin aktif | 1 |
| Permission Super Admin guard `web` | 44 |
| Kolom `users.company_id` | Tidak ada |
| Snapshot definisi role legacy | 8 |
| Snapshot nilai Company user legacy | 3 |

Verifikasi akhir:

- 58 tes dan 316 assertion lulus.
- PHPStan komponen inti lulus tanpa error.
- Pint file Tahap 13 lulus.
- Build produksi Vite lulus.
- Smoke API database aktif untuk Tim dan Admin lulus; seluruh data smoke di-rollback.
