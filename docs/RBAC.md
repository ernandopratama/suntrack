# Spesifikasi Final User, Role, Permission, dan Cakupan Data

**Project:** SunTrack  
**Status:** Disepakati sebagai dasar implementasi  
**Tanggal:** 29 Agustus 2026

## 1. Tujuan

Dokumen ini menetapkan rancangan final autentikasi dan otorisasi SunTrack. Implementasi harus mengikuti aturan dalam dokumen ini agar kontrol akses backend, tampilan frontend, dan cakupan data tetap konsisten.

SunTrack hanya memiliki tiga role internal:

1. `Super Admin`
2. `Admin`
3. `Tim`

Reviewer eksternal tetap menggunakan secure link. Reviewer eksternal tidak menjadi user internal dan tidak memiliki role.

## 2. Prinsip Akses

Kontrol akses menggunakan dua lapisan:

```text
Role dan permission menentukan tindakan yang boleh dilakukan.
Assignment Company dan Brand menentukan data yang boleh diakses.
```

Role tidak boleh digunakan sebagai pengganti cakupan data. `company_id` atau assignment tidak boleh digunakan sebagai pengganti permission.

Seluruh pembatasan wajib diterapkan pada backend. Penyembunyian menu dan tombol pada frontend berfungsi sebagai penyesuaian antarmuka, bukan pengamanan utama.

## 3. Definisi Role

### 3.1 Super Admin

Super Admin memiliki seluruh hak dan seluruh cakupan data.

Super Admin dapat:

- Melihat dan mengelola seluruh Company dan Brand.
- Melakukan seluruh operasi pada Campaign, Promotion, Task, Product, dan Variant.
- Melihat seluruh Activity Log dan menjalankan seluruh Export.
- Membuat, melihat, mengedit, dan menghapus user.
- Menetapkan role untuk seluruh user.
- Mengatur permission seluruh user.
- Mengatur assignment Company dan Brand seluruh user Tim.
- Mengakses System Settings, monitoring, audit, dan pengaturan hak akses.

Aturan pengaman:

- Sistem harus selalu menyisakan minimal satu Super Admin aktif.
- Super Admin terakhir tidak dapat dihapus atau diturunkan rolenya.
- Perubahan role, permission, dan assignment wajib dicatat pada Activity Log.

### 3.2 Admin

Admin memiliki cakupan operasional global terhadap seluruh Company dan Brand.

Admin dapat:

- Melihat seluruh user.
- Membuat user baru dengan role Tim.
- Mengedit profil user Tim.
- Mengatur permission user Tim.
- Mengatur assignment Company dan Brand user Tim.
- Melakukan CRUD Company.
- Melakukan CRUD Brand.
- Melakukan CRUD Campaign, Promotion, Task, Product, dan Variant.
- Mengatur proses Campaign dan Promotion, termasuk approval.
- Melihat Activity Log.
- Menjalankan Export.

Admin tidak dapat:

- Membuat Super Admin atau Admin.
- Mengedit role atau permission Super Admin.
- Mengedit role atau permission Admin lain.
- Mengedit role atau permission dirinya sendiri.
- Menghapus user.
- Mengakses System Settings dan pengaturan keamanan global.

### 3.3 Tim

Tim memiliki akses operasional dalam cakupan Company dan Brand yang ditugaskan.

Tim dapat:

- Melihat Company yang ditugaskan.
- Melihat Brand yang ditugaskan atau termasuk dalam Company yang ditugaskan.
- Melakukan CRUD Campaign dalam cakupannya.
- Melakukan CRUD Promotion dalam cakupannya.
- Melakukan CRUD Task dalam cakupannya.
- Melakukan CRUD Product dan Variant dalam cakupannya.
- Melihat Activity Log dalam cakupannya.
- Menjalankan Export untuk data dalam cakupannya.

Tim tidak dapat:

- Melihat menu pengelolaan user.
- Membuat, mengedit, atau menghapus user.
- Mengatur role dan permission.
- Mengatur assignment Company dan Brand.
- Membuat, mengedit, atau menghapus Company.
- Membuat, mengedit, atau menghapus Brand.
- Mengakses System Settings, monitoring global, atau audit global.

## 4. Matriks Hak Akses

| Modul atau tindakan | Super Admin | Admin | Tim |
|---|---:|---:|---:|
| Melihat seluruh user | Ya | Ya | Tidak |
| Membuat Super Admin | Ya | Tidak | Tidak |
| Membuat Admin | Ya | Tidak | Tidak |
| Membuat Tim | Ya | Ya | Tidak |
| Mengedit Super Admin | Ya | Tidak | Tidak |
| Mengedit Admin | Ya | Tidak | Tidak |
| Mengedit Tim | Ya | Ya | Tidak |
| Menghapus user | Ya | Tidak | Tidak |
| Mengatur role | Ya | Tidak | Tidak |
| Mengatur permission Tim | Ya | Ya | Tidak |
| Mengatur assignment Tim | Ya | Ya | Tidak |
| Company | CRUD | CRUD | View sesuai assignment |
| Brand | CRUD | CRUD | View sesuai assignment |
| Campaign | CRUD | CRUD | CRUD sesuai assignment |
| Promotion | CRUD dan approval | CRUD dan approval | CRUD sesuai assignment |
| Task | CRUD | CRUD | CRUD sesuai assignment |
| Product | CRUD | CRUD | CRUD sesuai assignment |
| Variant | CRUD | CRUD | CRUD sesuai assignment |
| Activity Log | Seluruh data | Seluruh data | Data sesuai assignment |
| Export | Seluruh data | Seluruh data | Data sesuai assignment |
| Hak Akses | Seluruh user | Tim saja | Tidak |
| System Settings | Ya | Tidak | Tidak |
| Monitoring dan audit global | Ya | Tidak | Tidak |

## 5. Daftar Permission

Permission menggunakan format `<module>.<action>`.

### 5.1 Access Control

```text
access.view
access.assign-role
access.assign-permission
access.assign-scope
```

### 5.2 User

```text
user.view
user.create
user.update
user.delete
```

### 5.3 Company dan Brand

```text
company.view
company.create
company.update
company.delete

brand.view
brand.create
brand.update
brand.delete
```

### 5.4 Campaign dan Promotion

```text
campaign.view
campaign.create
campaign.update
campaign.delete
campaign.approve

promotion.view
promotion.create
promotion.update
promotion.delete
promotion.approve
```

### 5.5 Task

```text
task.view
task.create
task.update
task.delete
```

### 5.6 Product dan Variant

```text
product.view
product.create
product.update
product.delete

variant.view
variant.create
variant.update
variant.delete
```

### 5.7 Activity dan Export

```text
activity.view
report.export
```

### 5.8 System

```text
settings.view
settings.update
system.monitor
audit.view
```

## 6. Strategi Permission per Role

### Super Admin

Super Admin selalu memperoleh seluruh permission. Implementasi dapat memakai seluruh permission yang disinkronkan ke role atau pemeriksaan global khusus Super Admin.

### Admin

Permission awal Admin mengikuti matriks dokumen ini. Super Admin dapat menonaktifkan atau mengaktifkan permission role Admin dalam whitelist `ADMIN_PERMISSIONS`. Permission terlarang seperti `user.delete` dan `access.assign-role` tidak dapat diberikan. Admin tidak dapat mengubah permission role sendiri.

### Tim

User Tim menerima permission operasional default berikut saat dibuat:

```text
company.view
brand.view
campaign.view
campaign.create
campaign.update
campaign.delete
promotion.view
promotion.create
promotion.update
promotion.delete
task.view
task.create
task.update
task.delete
product.view
product.create
product.update
product.delete
variant.view
variant.create
variant.update
variant.delete
activity.view
report.export
```

Super Admin dapat mengatur permission bersama role Tim melalui whitelist `TEAM_ALLOWED_PERMISSIONS`. Permission role diwarisi seluruh user Tim. Super Admin dan Admin juga dapat menyesuaikan direct permission setiap user Tim melalui whitelist yang sama. Permission pengelolaan user, role, Company, Brand, dan sistem tidak dapat diberikan kepada Tim.

Permission efektif Tim merupakan gabungan permission role dan direct permission user. Penghapusan permission bersama dari role tidak menghapus direct permission yang masih dimiliki user. Penyesuaian individual tetap dilakukan melalui halaman User dan Hak Akses.

## 7. Cakupan Data Tim

Satu user Tim dapat bertanggung jawab atas:

- Beberapa Company.
- Beberapa Brand.
- Gabungan beberapa Company dan Brand.

Relasi yang diperlukan:

```text
users
  ├── user_company_assignments ── companies
  └── user_brand_assignments ──── brands
```

### 7.1 Assignment Company

Jika Tim mendapat assignment Company:

- Tim dapat melihat Company tersebut.
- Tim dapat melihat seluruh Brand di bawah Company tersebut.
- Tim dapat mengakses seluruh data operasional yang terhubung ke Brand tersebut.
- Brand baru yang dibuat di bawah Company tersebut otomatis masuk ke cakupan Tim.

### 7.2 Assignment Brand

Jika Tim hanya mendapat assignment Brand:

- Tim dapat melihat Brand tersebut.
- Tim dapat melihat informasi Company induknya.
- Tim tidak dapat melihat Brand lain dalam Company yang sama.
- Tim hanya dapat mengakses data operasional yang terhubung ke Brand tersebut.

### 7.3 Gabungan Assignment

Cakupan efektif Tim adalah gabungan:

```text
Seluruh Brand dari Company yang ditugaskan
+
Brand yang ditugaskan langsung
```

Contoh:

| Assignment | Cakupan efektif |
|---|---|
| Company A | Company A dan seluruh Brand di bawahnya |
| Brand B1 | Company B sebagai referensi dan Brand B1 |
| Company A dan Brand B1 | Seluruh Brand Company A dan Brand B1 |
| Brand B1 dan Brand B2 | Kedua Brand beserta Company induknya sebagai referensi |

## 8. Aturan Cakupan per Modul

### Campaign

Campaign dapat diakses Tim jika `brand_id` Campaign termasuk dalam cakupan Brand efektif Tim.

### Promotion

Promotion dapat diakses Tim jika Brand Promotion termasuk dalam cakupan Tim atau Campaign induknya dapat diakses Tim.

### Task

Task dapat diakses Tim jika Campaign induknya dapat diakses Tim.

### Product dan Variant

Product dapat diakses Tim jika Brand Product termasuk dalam cakupan Tim. Variant mengikuti Product induknya.

### Activity Log

Activity Log Tim hanya memuat aktivitas entity yang berada dalam cakupan efektif Tim.

### Export

Export Tim hanya memuat data yang dapat dilihat user tersebut. Parameter request tidak boleh memperluas cakupan export.

## 9. Antarmuka Pengaturan Hak Akses

### 9.1 Tampilan Super Admin

Halaman `Hak Akses` untuk Super Admin menyediakan:

- Daftar seluruh user.
- Filter berdasarkan role dan Company.
- Role aktif setiap user.
- Permission efektif setiap user.
- Pengubahan role.
- Checklist permission Tim.
- Assignment beberapa Company.
- Assignment beberapa Brand.
- Ringkasan cakupan efektif.
- Riwayat perubahan akses.

### 9.2 Tampilan Admin

Halaman `Hak Akses` untuk Admin hanya menampilkan user Tim.

Admin dapat:

- Mengubah permission Tim dari whitelist yang disediakan.
- Mengubah assignment Company Tim.
- Mengubah assignment Brand Tim.
- Melihat ringkasan cakupan efektif Tim.

Admin tidak dapat membuka atau mengubah akses Super Admin, Admin lain, atau dirinya sendiri.

### 9.3 Form Assignment

Form assignment menyediakan:

- Multi-select Company.
- Multi-select Brand yang dikelompokkan berdasarkan Company.
- Penanda `Termasuk melalui Company` pada Brand yang otomatis tercakup.
- Ringkasan Company dan Brand yang dapat diakses.
- Validasi backend terhadap seluruh ID assignment.

## 10. Aturan Tampilan Frontend

Frontend membaca permission efektif user dari endpoint profil autentikasi.

Menu dan tombol ditampilkan berdasarkan permission:

- Menu Users hanya untuk Super Admin dan Admin.
- Menu Hak Akses hanya untuk Super Admin dan Admin.
- Admin hanya melihat user Tim pada halaman Hak Akses.
- Tim tidak melihat tombol tambah, edit, atau hapus Company.
- Tim tidak melihat tombol tambah, edit, atau hapus Brand.
- Tombol CRUD Campaign, Promotion, Task, Product, dan Variant mengikuti permission efektif Tim.
- Activity Log dan Export tetap terlihat jika user memiliki permission terkait.
- System Settings hanya terlihat untuk Super Admin.

Permission yang diwarisi dari role dan direct permissions harus digabungkan dengan `getAllPermissions()`. Frontend tidak boleh hanya menerima direct permissions.

## 11. Aturan Backend

Setiap endpoint terproteksi harus melewati tiga pemeriksaan:

1. User sudah terautentikasi.
2. User memiliki permission untuk tindakan tersebut.
3. Entity berada dalam cakupan data user.

Penerapan backend menggunakan:

- Middleware permission untuk akses tingkat modul atau route.
- Policy untuk tindakan terhadap entity tertentu.
- Query scope atau service cakupan untuk membatasi daftar data.
- Validasi assignment pada operasi create dan update.

Request langsung ke endpoint tanpa permission harus menghasilkan HTTP `403`. Entity di luar cakupan dapat menghasilkan HTTP `404` agar keberadaan data tidak terungkap.

Super Admin melewati pembatasan cakupan data. Admin memiliki cakupan global. Tim selalu memakai cakupan assignment.

## 12. Rancangan Tabel Assignment

### user_company_assignments

```text
id
user_id
company_id
assigned_by
created_at
updated_at
```

Kendala:

- Kombinasi `user_id` dan `company_id` harus unik.
- `user_id` hanya boleh menunjuk user dengan role Tim.
- Penghapusan user menghapus assignment terkait.
- Penghapusan Company menghapus assignment terkait setelah aturan penghapusan Company terpenuhi.

### user_brand_assignments

```text
id
user_id
brand_id
assigned_by
created_at
updated_at
```

Kendala:

- Kombinasi `user_id` dan `brand_id` harus unik.
- `user_id` hanya boleh menunjuk user dengan role Tim.
- Penghapusan user menghapus assignment terkait.
- Penghapusan Brand menghapus assignment terkait setelah aturan penghapusan Brand terpenuhi.

Field `users.company_id` dipertahankan selama masa transisi. Setelah seluruh query memakai assignment, field tersebut tidak lagi menjadi sumber otorisasi. Penghapusan field dilakukan pada migrasi terpisah setelah bukti kompatibilitas tersedia.

## 13. Aturan Penghapusan

- Admin tidak dapat menghapus user.
- Super Admin dapat menghapus user selama tidak menghapus Super Admin aktif terakhir.
- Company tidak dapat dihapus jika masih memiliki data operasional aktif yang dilindungi relasi database.
- Brand tidak dapat dihapus jika masih memiliki data operasional aktif yang dilindungi relasi database.
- Assignment tidak boleh melemahkan foreign key dan aturan integritas yang sudah ada.

## 14. Audit dan Cache

Activity Log wajib mencatat:

- Pembuatan user.
- Perubahan profil user.
- Penghapusan user.
- Perubahan role.
- Perubahan permission.
- Perubahan assignment Company.
- Perubahan assignment Brand.

Log memuat actor, target user, nilai sebelum perubahan, nilai setelah perubahan, dan waktu perubahan.

Cache permission Spatie dan cache cakupan data harus dibersihkan setelah role, permission, atau assignment berubah. Perubahan akses harus berlaku pada request berikutnya.

## 15. Urutan Pekerjaan Implementasi

Pekerjaan dijalankan sesuai urutan berikut. Setiap tahap harus memenuhi hasil dan verifikasi yang ditentukan sebelum tahap berikutnya dimulai.

### Status Pelaksanaan

| Tahap | Status | Bukti |
|---|---|---|
| 1. Audit sistem | Selesai | `docs/RBAC-AUDIT.md` |
| 2. Registry role dan permission | Selesai dan teruji | `app/Support/Rbac/RbacRegistry.php` dan `database/seeders/RolePermissionSeeder.php` |
| 3. Struktur assignment | Selesai dan teruji | Migrasi `user_company_assignments` dan `user_brand_assignments` |
| 4. Relasi dan layanan cakupan data | Selesai dan teruji | Relasi assignment dan `app/Services/Authorization/DataScopeService.php` |
| 5. Proteksi backend | Selesai dan teruji | Middleware permission, Policy, query scope, API assignment Tim, dan endpoint Activity Log |
| 6. Migrasi user dan assignment lama | Selesai, teruji, dan diaktifkan | Command `rbac:migrate-legacy-users`, normalisasi tipe user, snapshot rollback, dan migrasi seluruh user termasuk soft-deleted |
| 7. Kontrak data autentikasi | Selesai dan teruji | Endpoint login/profil mengirim role aktif, permission efektif, dan ringkasan scope |
| 8. Halaman User dan Hak Akses | Selesai dan teruji | Daftar/form user, halaman Role khusus Super Admin, assignment Company/Brand, scope efektif, dan riwayat akses |
| 9. Penyesuaian tampilan modul | Selesai dan teruji | Guard route, menu, tombol aksi, form, halaman akses ditolak, Activity Log, Export, dan dark mode tampilan baru |
| 10. Audit dan invalidasi cache | Selesai dan teruji | Activity Log menyimpan actor, target, nilai sebelum/sesudah, serta invalidasi cache permission dan scope |
| 11. Pengujian menyeluruh | Selesai dan teruji otomatis | Pengujian role, permission, scope, manipulasi ID, sesi aktif, migrasi, login, route, dan theme contract |
| 12. Aktivasi bertahap | Selesai dan terverifikasi | Backup PostgreSQL, migrasi, seeder, migrasi data, build produksi, dan smoke API tiga role |
| 13. Pembersihan data legacy | Selesai dan terverifikasi | Role legacy dihapus, `users.company_id` dihapus, kode transisi dibersihkan, dan arsip rollback tersedia |

Database aktif telah menjalankan seluruh Tahap 1-13. Role internal aktif hanya `Super Admin`, `Admin`, dan `Tim`. Cakupan Tim hanya berasal dari tabel assignment.

Verifikasi Tahap 7-9 pada 2 September 2026:

- Test suite: 53 tes dan 246 assertion lulus.
- Kontrak frontend RBAC: 3 tes dan 25 assertion lulus.
- PHPStan pada komponen inti Tahap 7-9: tidak ada error.
- Pint dan build produksi Vite: lulus.
- Route `api/v1/admin/access/options` memakai autentikasi Sanctum dan permission `access.view`.

Verifikasi Tahap 10-12 pada 2 September 2026:

- Backup PostgreSQL tersimpan di `storage/app/private/backups/rbac_pre_activation_20260902_134101.dump` dan arsip dapat dibaca oleh `pg_restore`.
- Seluruh migrasi database aktif berstatus selesai tanpa migrasi pending.
- Database aktif memiliki tiga role final dan 44 permission untuk guard `web`.
- Satu Super Admin aktif tetap tersedia.
- Satu user aktif dan satu user soft-deleted dengan role legacy `Company` telah dipensiunkan dari role internal; snapshot rollback tersimpan.
- Activity Log migrasi akses menyimpan dua catatan `Access Updated`.
- Smoke API database aktif lulus untuk profil Super Admin, pengelolaan akses Admin, Campaign/Activity Log/Export Tim, serta penolakan User dan Company pada Tim.
- Seluruh user dan token smoke dijalankan dalam transaksi rollback; tidak ada data smoke yang tertinggal.
- Test suite akhir: 60 tes dan 332 assertion lulus setelah seluruh perubahan dan aktivasi.
- PHPStan pada komponen RBAC: tidak ada error.
- Pint dan build produksi Vite: lulus.
- Verifikasi visual browser belum tersedia pada sesi aktivasi; theme contract, route guard, dan build produksi telah lulus.

Verifikasi Tahap 13 pada 3 September 2026:

- Audit ulang Tahap 1-12 lulus pada baseline 60 tes dan 332 assertion.
- Migrasi kontraksi role dan kolom legacy lulus maju, rollback, dan penerapan ulang pada database uji.
- Database aktif hanya memiliki tiga nama role final pada guard `web` dan `api`; tidak ada role atau assignment non-final.
- Kolom `users.company_id` sudah dihapus. Tiga nilai lama tersimpan pada `rbac_legacy_user_company_snapshots`.
- Delapan definisi role legacy lintas guard tersimpan pada `rbac_legacy_role_snapshots` untuk rollback.
- Kode otorisasi tidak lagi membaca `users.company_id`; cakupan Tim memakai `user_company_assignments` dan `user_brand_assignments`.
- Search dan Pricing Analytics untuk Super Admin/Admin memakai cakupan global dan tidak bergantung pada Company user.
- Test suite akhir: 58 tes dan 316 assertion lulus setelah tes kompatibilitas transisi dihapus.
- PHPStan komponen inti: tidak ada error. Pint file Tahap 13 dan build produksi Vite lulus.
- Smoke database aktif lulus: profil Tim `200`, Company scope Tim `200` dengan satu hasil, User/Company create Tim `403`, Search Admin `200`, dan Pricing Admin `200`.
- Data user, Company, token, dan entity smoke dibungkus transaksi rollback; tidak ada data smoke tertinggal.

Verifikasi halaman Role pada 3 September 2026:

- Menu dan route `/roles` hanya tersedia untuk Super Admin.
- Halaman hanya menampilkan `Super Admin`, `Admin`, dan `Tim` dari guard `web`.
- Permission Super Admin terkunci. Permission Admin dan Tim hanya dapat dipilih dari whitelist registry masing-masing.
- Perubahan permission disinkronkan ke guard `web` dan `api`, dibersihkan dari cache, dan dicatat pada Activity Log.
- Modal user menampilkan user aktif pemilik role dengan pagination; user soft-deleted tidak ditampilkan.
- Endpoint create, rename, dan delete role tidak tersedia.
- Test suite: 65 tes dan 354 assertion lulus. PHPStan komponen Role dan build produksi Vite lulus.
- Pemeriksaan visual browser tidak tersedia pada sesi ini.

Rollback setelah Tahap 13:

- Backup sebelum kontraksi tersedia di `storage/app/private/backups/rbac_pre_legacy_cleanup_20260902_073231.dump`; arsip custom PostgreSQL berukuran 96.971 byte dan memiliki 245 baris TOC.
- `php artisan migrate:rollback --step=2` memulihkan definisi role legacy dan nilai `users.company_id` dari tabel snapshot.
- Rollback penuh ke kondisi sebelum Tahap 13 dilakukan dengan menghentikan aplikasi dan memulihkan backup PostgreSQL tersebut bersama versi kode sebelum Tahap 13.

### Tahap 1 - Audit Sistem Saat Ini

Pekerjaan:

- Mencatat seluruh role dan permission yang ada di database.
- Mencatat user beserta role, direct permission, dan `company_id` saat ini.
- Menelusuri route, controller, middleware, Policy, query, form, menu, dan tombol yang memakai role atau Company.
- Menentukan pemetaan setiap role lama ke `Super Admin`, `Admin`, atau `Tim`.
- Menyimpan hasil audit sebagai acuan migrasi dan pengujian regresi.

Hasil tahap:

- Daftar titik perubahan kode.
- Daftar user yang perlu dimigrasikan.
- Tabel pemetaan role lama ke role final yang telah ditinjau.

Verifikasi:

- Tidak ada pemeriksaan role atau cakupan data yang terlewat dari inventaris.
- Belum ada role, permission, atau data user yang diubah.

### Tahap 2 - Menetapkan Registry Role dan Permission

Pekerjaan:

- Menetapkan nama role final: `Super Admin`, `Admin`, dan `Tim`.
- Membuat satu registry permission berdasarkan Bagian 5.
- Menetapkan permission awal dan whitelist konfigurasi untuk Super Admin, Admin, dan Tim.
- Menetapkan permission default dan whitelist permission untuk Tim.
- Membuat seeder role dan permission yang aman dijalankan berulang kali.

Hasil tahap:

- Satu sumber definisi role dan permission.
- Seeder tidak membuat data duplikat ketika dijalankan kembali.

Verifikasi:

- Matriks permission hasil seeder sama dengan Bagian 4.
- Tim tidak dapat menerima permission pengelolaan user, Company, Brand, role, atau sistem.

### Tahap 3 - Menambahkan Struktur Assignment

Pekerjaan:

- Membuat migrasi `user_company_assignments`.
- Membuat migrasi `user_brand_assignments`.
- Menambahkan unique constraint, foreign key, dan index yang diperlukan.
- Mempertahankan `users.company_id` selama masa transisi.
- Menyediakan langkah rollback untuk kedua migrasi baru.

Hasil tahap:

- Struktur database siap menyimpan beberapa Company dan Brand untuk satu user Tim.

Verifikasi:

- Migrasi dapat dijalankan dan di-rollback pada database uji.
- Kombinasi assignment duplikat ditolak database.
- Data lama tetap utuh.

### Tahap 4 - Membuat Relasi dan Layanan Cakupan Data

Pekerjaan:

- Menambahkan relasi User ke Company dan Brand assignment.
- Membuat layanan terpusat untuk menghitung cakupan efektif Tim.
- Menggabungkan Brand dari Company assignment dengan Brand assignment langsung.
- Menambahkan query scope yang dapat digunakan ulang oleh seluruh modul.
- Menetapkan Super Admin dan Admin sebagai cakupan global.

Hasil tahap:

- Satu sumber logika cakupan data untuk seluruh backend.

Verifikasi:

- Assignment Company mencakup seluruh Brand di bawahnya.
- Assignment Brand tidak membuka Brand lain pada Company yang sama.
- Gabungan assignment tidak menghasilkan data duplikat.

### Tahap 5 - Menerapkan Proteksi Backend

Urutan modul backend:

1. User dan Hak Akses.
2. Company dan Brand.
3. Campaign dan Promotion.
4. Task.
5. Product dan Variant.
6. Activity Log dan Export.
7. System Settings dan monitoring.

Pekerjaan pada setiap modul:

- Memasang middleware autentikasi dan permission pada route.
- Membuat atau menyesuaikan Policy untuk tindakan terhadap entity.
- Menerapkan query scope pada daftar, pencarian, relasi, dan pilihan form.
- Memvalidasi cakupan pada operasi create, update, delete, approval, dan export.
- Menolak request tanpa permission dengan HTTP `403`.
- Menolak akses entity di luar cakupan dengan HTTP `404` bila keberadaan data perlu disembunyikan.

Hasil tahap:

- API mematuhi permission dan cakupan data walaupun dipanggil tanpa frontend.

Verifikasi:

- Pengujian backend untuk satu modul harus lulus sebelum berlanjut ke modul berikutnya.
- Parameter request tidak dapat digunakan untuk melewati cakupan user.

### Tahap 6 - Memigrasikan User dan Assignment Lama

Pekerjaan:

- Membuat backup database sebelum migrasi data.
- Memetakan user lama ke salah satu dari tiga role final berdasarkan hasil audit.
- Mengubah role hanya setelah pemetaan user tersebut disetujui.
- Mengonversi `users.company_id` menjadi assignment Company jika sesuai.
- Menetapkan permission Tim dan assignment tambahan secara eksplisit.
- Membuat laporan user sebelum dan sesudah migrasi.

Hasil tahap:

- Seluruh user memiliki role final dan cakupan yang dapat ditelusuri.

Verifikasi:

- Tidak ada user kehilangan akses yang masih diperlukan.
- Tidak ada user memperoleh akses lebih luas secara otomatis.
- Minimal satu Super Admin aktif tetap tersedia.

Role lama tidak boleh langsung dihapus. User yang memakai `Brand Manager`, `Finance Auditor`, `Operational Staff`, atau `Company` harus ditinjau dan dipetakan ke role final secara eksplisit.

### Tahap 7 - Menyesuaikan Kontrak Data Autentikasi

Pekerjaan:

- Mengirim role aktif dan permission efektif melalui endpoint profil autentikasi.
- Menggunakan `getAllPermissions()` untuk menggabungkan permission role dan direct permission.
- Mengirim ringkasan cakupan yang diperlukan frontend tanpa mengekspos data yang tidak boleh dilihat.
- Memastikan perubahan akses berlaku pada request berikutnya.

Hasil tahap:

- Frontend menerima sumber data akses yang konsisten dari backend.

Verifikasi:

- Payload Super Admin, Admin, dan Tim sesuai dengan permission efektif masing-masing.
- Perubahan permission dan assignment terlihat setelah cache dibersihkan.

### Tahap 8 - Membuat Halaman User dan Hak Akses

Urutan antarmuka:

1. Daftar dan form user.
2. Halaman Hak Akses Super Admin.
3. Halaman Hak Akses Admin untuk user Tim.
4. Multi-select Company dan Brand.
5. Ringkasan cakupan efektif.
6. Riwayat perubahan akses.
7. Halaman Role khusus Super Admin untuk mengatur permission Admin/Tim dan melihat user pemilik role.

Verifikasi:

- Super Admin dapat mengelola seluruh user sesuai aturan.
- Admin hanya dapat membuat dan mengedit Tim.
- Admin tidak dapat menghapus user atau mengubah Admin dan Super Admin.
- Tim tidak dapat membuka halaman User dan Hak Akses.
- Admin dan Tim tidak dapat membuka halaman maupun API Role.
- Super Admin tidak dapat mengubah permission role Super Admin atau membuat, mengganti nama, dan menghapus role final.

### Tahap 9 - Menyesuaikan Seluruh Tampilan Modul

Pekerjaan:

- Menyesuaikan menu berdasarkan permission efektif.
- Menyesuaikan tombol create, update, delete, approval, dan export.
- Membatasi pilihan Company dan Brand pada form Tim.
- Menyesuaikan empty state dan pesan akses ditolak.
- Memastikan dark mode tetap diterapkan pada tampilan baru.

Hasil tahap:

- Tampilan mengikuti hak akses backend tanpa menjadi sumber keputusan keamanan.

Verifikasi:

- Setiap role hanya melihat menu, tombol, dan pilihan data yang relevan.
- Request manual tetap ditolak backend ketika kontrol frontend disembunyikan.

### Tahap 10 - Menambahkan Audit dan Invalidasi Cache

Pekerjaan:

- Mencatat perubahan role, permission, dan assignment.
- Menyimpan actor, target, nilai sebelum, nilai sesudah, dan waktu perubahan.
- Membersihkan cache permission Spatie setelah perubahan akses.
- Membersihkan cache cakupan setelah perubahan assignment.

Verifikasi:

- Setiap perubahan akses mempunyai Activity Log yang lengkap.
- Hak akses baru berlaku tanpa menunggu login ulang jika sesi masih valid.

### Tahap 11 - Pengujian Menyeluruh

Pengujian dilakukan pada level unit, feature, dan antarmuka.

Skenario minimum:

- Setiap tindakan pada matriks Super Admin, Admin, dan Tim.
- Akses lintas Company dan lintas Brand.
- Assignment Company, Brand, dan gabungan keduanya.
- Manipulasi ID melalui URL, request body, filter, relasi, dan export.
- Perubahan permission saat user masih memiliki sesi aktif.
- Proteksi Super Admin aktif terakhir.
- Migrasi maju dan rollback.
- Regresi login menggunakan username atau email.
- Tampilan light mode dan dark mode pada halaman baru.

Hasil tahap:

- Seluruh acceptance criteria pada Bagian 16 mempunyai pengujian yang lulus.

### Tahap 12 - Aktivasi Bertahap

Urutan aktivasi:

1. Jalankan migrasi struktur database.
2. Jalankan seeder role dan permission.
3. Jalankan migrasi data user yang telah ditinjau.
4. Aktifkan proteksi backend.
5. Aktifkan antarmuka Hak Akses dan navigasi baru.
6. Jalankan pemeriksaan cepat untuk ketiga role.
7. Pantau error otorisasi dan Activity Log.

Verifikasi:

- Login, navigasi, operasi utama, Activity Log, dan Export berjalan sesuai role.
- Tidak ditemukan akses lintas cakupan pada pemeriksaan setelah aktivasi.

### Tahap 13 - Pembersihan Data Legacy

Tahap ini dikerjakan pada migrasi terpisah setelah sistem baru stabil dan seluruh pengujian lulus.

Status: selesai dan telah diterapkan pada database aktif tanggal 2 September 2026.

Pekerjaan:

- Memastikan tidak ada user yang masih memakai role lama.
- Memastikan tidak ada kode yang membaca `users.company_id` untuk otorisasi.
- Menghapus role lama dari database.
- Menghapus `users.company_id` jika sudah tidak digunakan untuk kebutuhan lain.
- Menghapus kode transisi dan pengujian khusus kompatibilitas lama.

Verifikasi:

- Database hanya memiliki tiga role internal final.
- Seluruh cakupan Tim berasal dari tabel assignment.
- Pengujian regresi tetap lulus setelah data legacy dihapus.

Implementasi:

- Migrasi `2026_09_02_000002_retire_legacy_rbac_roles` menolak kontraksi bila role legacy masih memiliki assignment, mengarsipkan definisi dan permission role, lalu menghapus role non-final.
- Migrasi `2026_09_02_000003_remove_company_id_from_users_table` menolak kontraksi bila cakupan Tim belum dipindahkan, mengarsipkan nilai lama, lalu menghapus kolom.
- Fallback scope, input/payload `company_id` milik User, relasi User-Company lama, command migrasi transisi, dan tes kompatibilitas lama telah dihapus.
- Tabel snapshot dipertahankan sebagai data rollback dan tidak digunakan oleh otorisasi aktif.

### Aturan Pelaksanaan

- Satu tahap diselesaikan dan diverifikasi sebelum tahap berikutnya dimulai.
- Perubahan skema dan migrasi data dibuat terpisah.
- Pembersihan data legacy tidak digabung dengan aktivasi awal.
- Setiap perubahan harus memiliki pengujian sesuai risiko aksesnya.
- Jika hasil implementasi berbeda dari dokumen ini, dokumen harus diperbarui dan disepakati sebelum pekerjaan dilanjutkan.

## 16. Acceptance Criteria

Implementasi dianggap selesai jika seluruh kondisi berikut terpenuhi:

- Database hanya memiliki role internal `Super Admin`, `Admin`, dan `Tim` setelah masa transisi selesai.
- Super Admin dapat mengelola seluruh user, role, permission, dan assignment.
- Sistem selalu memiliki minimal satu Super Admin aktif.
- Admin hanya dapat membuat dan mengedit user Tim.
- Admin tidak dapat menghapus user.
- Admin tidak dapat mengubah Super Admin, Admin lain, atau dirinya sendiri.
- Admin dapat melakukan CRUD Company dan Brand.
- Admin dapat melakukan CRUD seluruh modul operasional.
- Tim tidak dapat mengakses API pengelolaan user, Company, Brand, role, dan permission.
- Tim dapat melakukan CRUD modul operasional sesuai permission dan assignment.
- Assignment Company memberikan akses ke seluruh Brand sekarang dan yang dibuat kemudian.
- Assignment Brand tidak membuka Brand lain dalam Company yang sama.
- Activity Log Tim hanya menampilkan data dalam cakupannya.
- Export Tim tidak dapat memuat data di luar cakupannya.
- Menu dan tombol frontend mengikuti permission efektif.
- Request langsung ke backend tetap ditolak ketika menu atau tombol disembunyikan.
- Seluruh perubahan hak akses tercatat dalam Activity Log.
- Pengujian lintas role dan lintas Company lulus.

## 17. Batas Implementasi

- Reviewer secure link tidak diubah menjadi user internal.
- Tidak ada role internal selain tiga role final.
- Admin tidak dapat membuat role baru.
- Tim tidak dapat mengubah permission atau assignment sendiri.
- Penghapusan field legacy dilakukan setelah transisi selesai, bukan pada tahap awal.
