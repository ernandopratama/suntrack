# SUNTRACK Enterprise - Data Model, Acceptance Criteria, dan Implementation Plan

## 1. Baseline dan Batas Implementasi

Baseline implementasi adalah commit `32cb84c` dan keputusan produk pada:

- `docs/SUNTRACK_Enterprise_PRD_v1.0.md`
- `docs/SUNTRACK_Enterprise_PRD_GAP_ANALYSIS.md`
- `docs/RBAC.md`

Implementasi mencakup core Campaign, Task, Performance Report, serta Collaboration dan Delivery:

- ownership dan data scope;
- lifecycle dengan transition guard;
- audit status dan perubahan assignment;
- permission Report dan review Task;
- API serta tampilan operasional dasar;
- migration data lama yang dapat dibalik;
- feature test untuk actor, scope, transition, dan preservasi data.

Attachment generik, chat berbalas, access log per kunjungan, reminder priority, serta KPI Task/Report telah diaktifkan setelah core lifecycle lulus pengujian.

## 2. Data Model Target

### 2.1 Campaign

Tabel `campaigns` mempertahankan primary key UUID dan seluruh data lama.

| Field | Tipe | Aturan |
|---|---|---|
| `brand_id` | UUID FK | Wajib; sumber data scope. |
| `created_by` | UUID FK nullable | Pembuat tetap; wajib untuk data baru. Nullable menjaga kompatibilitas data lama. |
| `pic_id` | UUID FK nullable | Super Admin/Admin; wajib sebelum `assigned`. |
| `name` | string | Wajib. |
| `objective` | text nullable | Tujuan Campaign. |
| `description` | text nullable | Deskripsi kerja. |
| `priority` | string | `normal`, `mid`, atau `urgent`. |
| `start_date` | datetime nullable | Tanggal mulai. |
| `end_date` | datetime nullable | Target akhir lama; dipertahankan. |
| `deadline` | datetime nullable | Wajib sebelum `assigned`. |
| `status` | string | Status canonical Campaign. |
| `notes` | text nullable | Catatan umum. |
| `approval_notes` | text nullable | Catatan approval/revision terakhir. |
| `completed_at` | datetime nullable | Diisi saat `completed`. |

Tabel `campaign_members`:

| Field | Tipe | Aturan |
|---|---|---|
| `campaign_id` | UUID FK | Cascade saat Campaign dihapus permanen. |
| `user_id` | UUID FK | Harus role Tim dan berada dalam scope Brand. |
| `assigned_by` | UUID FK nullable | Super Admin/Admin pemberi assignment. |
| timestamps | datetime | Waktu assignment. |

Pasangan `campaign_id` dan `user_id` unik.

### 2.2 Task

Tabel `tasks` mempertahankan field visual submission lama.

| Field | Tipe | Aturan |
|---|---|---|
| `brand_id` | UUID FK nullable | Dibackfill dari Campaign; wajib untuk data baru. |
| `campaign_id` | UUID FK nullable | Opsional; Brand harus sama dengan Brand Task. |
| `created_by` | UUID FK nullable | Pembuat tetap. |
| `pic_id` | UUID FK nullable | Super Admin/Admin; wajib sebelum `assigned`. |
| `assignee_id` | UUID FK nullable | Satu user Tim dalam scope; wajib sebelum `assigned`. |
| `name` | string | Wajib. |
| `description` | text nullable | Instruksi Task. |
| `priority` | string | `normal`, `mid`, atau `urgent`. |
| `progress_status` | string | Status canonical Task; nama kolom dipertahankan untuk kompatibilitas. |
| `deadline` | datetime nullable | Wajib sebelum `assigned`. |
| `notes` | text nullable | Catatan umum. |
| `hold_reason` | text nullable | Wajib ketika masuk `on_hold`. |
| `revision_notes` | text nullable | Wajib ketika masuk `revision`. |
| `completion_summary` | text nullable | Wajib sebelum `waiting_review`. |
| `completion_details` | text nullable | Detail hasil pekerjaan. |
| `completed_at` | datetime nullable | Diisi saat `completed`. |

### 2.3 Performance Report

Entity baru menggunakan tabel `performance_reports` agar tidak berbenturan dengan analytical export yang sudah memakai route `reports`.

| Field | Tipe | Aturan |
|---|---|---|
| `id` | UUID | Primary key. |
| `brand_id` | UUID FK | Wajib; sumber data scope. |
| `created_by` | UUID FK | Pembuat tetap. |
| `author_id` | UUID FK | Super Admin/Admin/Tim dalam scope. |
| `pic_id` | UUID FK | Super Admin/Admin. |
| `supersedes_report_id` | UUID FK nullable | Report published yang digantikan versi baru. |
| `report_type` | string | `daily` atau `monthly`. |
| `title` | string | Wajib. |
| `period_start` | date | Wajib. |
| `period_end` | date | Wajib dan tidak sebelum `period_start`. |
| `executive_summary` | text nullable | Wajib sebelum review. |
| `content` | text nullable | Konten report; wajib sebelum review. |
| `status` | string | Status canonical Report. |
| `version` | unsigned integer | Mulai dari 1. |
| `review_notes` | text nullable | Catatan revision/review terakhir. |
| `approved_at` | datetime nullable | Diisi saat `approved`. |
| `published_at` | datetime nullable | Diisi saat `published`. |
| timestamps, soft delete | datetime | Audit dasar dan pemulihan. |

### 2.4 Collaboration dan Delivery

| Tabel | Tujuan |
|---|---|
| `attachments` | File polymorphic untuk Campaign, Task, Report, dan Comment. |
| perubahan `comments` | Mendukung Task/Report, `parent_id`, serta attachment. |
| `secure_link_access_logs` | Satu record immutable per akses Secure Link. |
| `comment_reads` | Status baca setiap pesan untuk setiap user internal. |
| perubahan `tasks` | Jadwal reminder berikutnya, waktu reminder terakhir, dan jumlah reminder. |

File disimpan pada disk privat dan hanya diunduh melalui endpoint yang memvalidasi permission, data scope, atau token Secure Link aktif.

## 3. Acceptance Criteria

### 3.1 Role, Ownership, dan Scope

| ID | Acceptance criteria |
|---|---|
| ACL-01 | Sistem hanya memakai role internal Super Admin, Admin, dan Tim. |
| ACL-02 | Super Admin dan Admin dapat melihat Campaign, Task, dan Report lintas Company/Brand. |
| ACL-03 | Tim hanya dapat melihat object dengan `brand_id` di effective Brand scope. |
| ACL-04 | ID Brand, Campaign, PIC, member, author, atau assignee di luar aturan menghasilkan 404 atau 422 tanpa menyimpan data. |
| ACL-05 | PIC harus Super Admin/Admin; Campaign member dan Task assignee harus Tim. |
| ACL-06 | Assignment object tidak memperluas effective Brand scope user. |
| ACL-07 | `created_by` dan author tidak berubah ketika PIC atau assignee diganti. |

### 3.2 Campaign

| ID | Acceptance criteria |
|---|---|
| CAM-AC-01 | Campaign baru tersimpan sebagai `draft`, mempunyai `created_by`, Brand, priority, dan data identitas. |
| CAM-AC-02 | `draft -> assigned` ditolak sampai PIC, minimal satu member Tim, dan deadline valid. |
| CAM-AC-03 | Member Tim Campaign dapat menjalankan transition kerja hanya ketika memiliki permission update dan scope Brand. |
| CAM-AC-04 | `waiting_review -> revision` memerlukan catatan dan hanya Super Admin/Admin. |
| CAM-AC-05 | `waiting_review -> approved` memerlukan `campaign.approve`. |
| CAM-AC-06 | `approved -> completed` hanya Super Admin/Admin dan mengisi `completed_at`. |
| CAM-AC-07 | Transition yang tidak terdaftar ditolak dengan 422. |
| CAM-AC-08 | Setiap transition menyimpan actor, old/new status, note, dan timestamp dalam Activity Log. |
| CAM-AC-09 | Data Campaign lama tetap ada dan statusnya dipetakan ke status canonical. |

### 3.3 Task

| ID | Acceptance criteria |
|---|---|
| TMS-AC-01 | Task baru dapat mandiri pada Brand atau terhubung ke Campaign dengan Brand yang sama. |
| TMS-AC-02 | Task baru berstatus `pending` dan menyimpan creator serta priority. |
| TMS-AC-03 | `pending -> assigned` memerlukan PIC, assignee Tim, instruksi, priority, dan deadline. |
| TMS-AC-04 | Assignee Tim dapat menjalankan transition kerja dalam scope; Tim lain tidak dapat mengambil alih tanggung jawab object. |
| TMS-AC-05 | `in_progress -> on_hold` memerlukan alasan. |
| TMS-AC-06 | `in_progress -> waiting_review` memerlukan completion summary dan evidence visual ketika `requires_visual=true`. |
| TMS-AC-07 | Revision dan completion hanya diputuskan Super Admin/Admin; revision memerlukan catatan. |
| TMS-AC-08 | Completion mengisi `completed_at`; transition keluar dari status terminal ditolak. |
| TMS-AC-09 | Task lama tetap ada, memperoleh `brand_id` dari Campaign, dan status dipetakan ke canonical. |

### 3.4 Performance Report

| ID | Acceptance criteria |
|---|---|
| PMS-AC-01 | User berpermission `performance-report.create` dapat membuat Report dalam Brand scope. |
| PMS-AC-02 | Author Tim harus berada dalam Brand scope; PIC harus Super Admin/Admin. |
| PMS-AC-03 | Draft hanya dapat dikirim review ketika periode, summary, dan content lengkap. |
| PMS-AC-04 | Revision dan approval hanya Super Admin/Admin; revision memerlukan catatan. |
| PMS-AC-05 | Publishing hanya Super Admin/Admin dengan permission publish dan hanya dari `approved`. |
| PMS-AC-06 | Report `published` tidak dapat diedit. Perubahan dibuat melalui endpoint versi baru yang menghasilkan `draft`. |
| PMS-AC-07 | Tim tidak dapat membaca Report di luar effective Brand scope. |
| PMS-AC-08 | Setiap transition dan pembuatan versi tercatat dalam Activity Log. |

### 3.5 Migration dan Operasional

| ID | Acceptance criteria |
|---|---|
| MIG-AC-01 | Migration dapat dijalankan pada PostgreSQL produksi dan SQLite test. |
| MIG-AC-02 | Migration tidak menghapus Campaign, Task, visual submission, atau analytical report lama. |
| MIG-AC-03 | Migration `down` memetakan status canonical kembali ke nilai lama sebelum melepas kolom/tabel baru. |
| MIG-AC-04 | Seeder permission bersifat idempotent dan tidak menghapus direct permission user. |
| MIG-AC-05 | Deployment dapat menjalankan `php artisan migrate --force` dan `php artisan db:seed --class=RolePermissionSeeder --force` berulang. |

### 3.6 Collaboration dan Delivery

| ID | Acceptance criteria |
|---|---|
| COL-AC-01 | Campaign, Task, Report, dan Comment dapat memiliki attachment polymorphic pada storage privat. |
| COL-AC-02 | Attachment hanya dapat diakses melalui permission dan scope internal atau Secure Link aktif untuk object yang sama. |
| COL-AC-03 | Task dan Report memiliki thread diskusi; reply wajib mengarah ke pesan dalam thread yang sama. |
| COL-AC-04 | Unread count dan waktu baca pesan dicatat per user internal. |
| COL-AC-05 | Secure Link Task hanya dibuat setelah `completed`; Secure Link Report hanya setelah `published`. |
| COL-AC-06 | Setiap akses publik menghasilkan access log immutable dan menaikkan view count. |
| COL-AC-07 | Reminder Task memakai interval konfigurasi priority, tidak dikirim pada status terminal/waiting review, dan tidak berulang sebelum interval berikutnya. |
| COL-AC-08 | Dashboard menampilkan KPI Task/Report sesuai effective Brand scope dan menyediakan drill-down. |

## 4. Fase Implementasi

### Fase A - Expand dan Data Migration

1. Tambah kolom ownership dan lifecycle tanpa menghapus field lama.
2. Buat pivot Campaign member dan tabel Performance Report.
3. Backfill `created_by`, `brand_id` Task, dan status canonical.
4. Tambah index dan foreign key yang aman untuk data lama.
5. Jalankan migration test serta verifikasi jumlah row sebelum/sesudah.

Rollback sebelum fitur dipakai: petakan status kembali, lalu hapus tabel/kolom baru. Migration menolak rollback otomatis jika sudah terdapat Performance Report, Campaign member, Task mandiri, atau field baru berisi data yang tidak dapat direpresentasikan schema lama. Data tersebut harus diekspor atau dimigrasikan secara eksplisit sebelum kontraksi.

### Fase B - Campaign Core

1. Perbarui enum, model, validation, resource, dan query.
2. Tambah sinkronisasi member yang memvalidasi role dan scope.
3. Tambah endpoint transition guarded.
4. Tambah feature test CAM-AC-01 sampai CAM-AC-09.

### Fase C - Task Core

1. Perbarui model Task agar Brand wajib pada writer baru dan Campaign opsional.
2. Tambah ownership, priority, completion data, dan transition guarded.
3. Perbarui data scope agar memakai relasi Brand langsung.
4. Tambah feature test TMS-AC-01 sampai TMS-AC-09.

### Fase D - Performance Report Core

1. Tambah model, policy, repository/controller, request, resource, dan route terpisah.
2. Tambah transition review, revision, approval, publish, serta versioning published Report.
3. Tambah permission dan data scope Report.
4. Tambah halaman daftar/form operasional dasar.
5. Tambah feature test PMS-AC-01 sampai PMS-AC-08.

### Fase E - Collaboration dan Delivery

1. Attachment polymorphic.
2. Task chat dan Report discussion.
3. Secure Link Task/Report dan access log per kunjungan.
4. Notification event dan reminder priority.
5. Dashboard KPI serta drill-down Task/Report.

### Fase F - Contract

Persetujuan pelaksanaan telah diberikan. Kontraksi tetap menunggu satu siklus produksi stabil dan backup tervalidasi:

1. Jadikan field hasil backfill non-null bila seluruh row valid.
2. Hapus compatibility alias payload yang tidak lagi dipakai frontend/client.
3. Hapus struktur legacy hanya setelah backup, metrik penggunaan, dan rollback window disepakati.

Audit read-only tersedia melalui `php artisan suntrack:contract-audit`. Rincian terdapat pada `docs/SUNTRACK_Enterprise_PHASE_F_CONTRACT.md`. Migration destruktif tidak berada dalam release Fase E.

## 5. Urutan Deploy Aman

```text
Backup database
  -> maintenance mode
  -> deploy kode yang kompatibel
  -> migrate --force
  -> seed RolePermissionSeeder
  -> clear/cache framework
  -> smoke test role, scope, dan transition
  -> aktifkan aplikasi
  -> pantau log, queue, dan failed jobs
```

Verifikasi minimum produksi:

- jumlah Campaign dan Task sebelum/sesudah sama;
- tidak ada Task lama tanpa Brand setelah backfill;
- tidak ada status di luar daftar canonical;
- role dan permission baru tersedia;
- Super Admin dapat mengakses seluruh modul;
- Tim tidak dapat mengakses Brand di luar assignment;
- transition invalid menghasilkan 422 dan tidak mengubah status.

## 6. Status Eksekusi

| Fase | Status | Bukti |
|---|---|---|
| Data model dan acceptance criteria | Selesai | Bagian 2 dan 3 dokumen ini. |
| Fase A - Expand dan Data Migration | Selesai | Tiga migration `2026_09_05_000001` sampai `2026_09_05_000003`; uji `up -> rollback -> up` lulus. |
| Fase B - Campaign Core | Selesai | Ownership, member Tim, status canonical, transition guard, Activity Log, API, dan form diperbarui. |
| Fase C - Task Core | Selesai | Brand langsung, Campaign opsional, PIC/assignee, completion data, transition guard, API, dan form diperbarui. |
| Fase D - Performance Report Core | Selesai | CRUD, scope, review, approval, publish, versioning, menu, halaman, dan permission tersedia. |
| Fase E - Collaboration dan Delivery | Selesai | Migration `2026_09_05_000004`, storage privat, chat/reply/unread, Secure Link Task/Report, access log, notification log, scheduler reminder priority, KPI dashboard, UI, dan feature test tersedia. |
| Fase F - Contract | Guard selesai; kontraksi menunggu observasi | Command `suntrack:contract-audit` dan checklist kontraksi tersedia. Schema destruktif ditunda sampai observasi produksi minimal tujuh hari serta backup tervalidasi. |

Feature test berada pada `tests/Feature/EnterpriseWorkflowTest.php` dan `tests/Feature/EnterpriseCollaborationTest.php`. Quality gate mencakup seluruh Feature test, PHPStan, Pint, build Vite, dan migration rollback proof.
