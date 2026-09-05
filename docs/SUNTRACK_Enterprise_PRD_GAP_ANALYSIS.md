# SUNTRACK Enterprise PRD v1.0 - Gap Analysis

## 1. Tujuan dan Baseline

Dokumen ini membandingkan target pada `SUNTRACK_Enterprise_PRD_v1.0.md` dengan implementasi SUNTRACK pada baseline commit `32cb84c` tanggal 05 September 2026.

Status dokumen: final untuk gap analysis, role/ownership/data scope, dan lifecycle. Dokumen ini belum memberikan otorisasi implementasi.

Status penilaian:

- `Sesuai`: kebutuhan utama sudah tersedia dan selaras.
- `Parsial`: sebagian kemampuan tersedia, tetapi kontrak data atau alurnya belum lengkap.
- `Tidak selaras`: kemampuan tersedia dengan model atau lifecycle yang berbeda dari keputusan PRD.
- `Belum tersedia`: belum ada domain, data model, API, atau antarmuka yang memenuhi kebutuhan.

Artefak utama yang diaudit:

| Area | Sumber implementasi |
|---|---|
| Role dan permission | `app/Support/Rbac/RbacRegistry.php`, `app/Policies/UserPolicy.php`, `docs/RBAC.md` |
| Data scope | `app/Services/Authorization/DataScopeService.php`, `app/Models/User.php`, `app/Repositories/*Repository.php` |
| Campaign | `app/Models/Campaign.php`, `app/Enums/CampaignStatus.php`, `app/Http/Controllers/Api/V1/CampaignController.php` |
| Task | `app/Models/Task.php`, `app/Enums/TaskStatus.php`, `app/Http/Requests/StoreTaskRequest.php`, `app/Http/Requests/UpdateTaskRequest.php` |
| Report | `app/Http/Controllers/Api/V1/ReportController.php`, `app/Services/Reporting/ReportingService.php` |
| Secure Link dan API | `app/Models/SecureLink.php`, `app/Http/Controllers/Api/V1/SecureLinkController.php`, `routes/api.php` |
| Notification dan dashboard | `app/Services/Notification/NotificationService.php`, `app/Repositories/DashboardRepository.php` |

## 2. Ringkasan Eksekutif

| Area | Status | Ringkasan |
|---|---|---|
| Role dan permission | Sesuai | Role internal final adalah Super Admin, Admin, dan Tim. Client menggunakan akses eksternal melalui Secure Link. |
| Data scope | Sesuai untuk modul lama | Super Admin dan Admin bersifat global. Tim dibatasi oleh assignment Company dan Brand. Report operasional belum tercakup karena domainnya belum tersedia. |
| Campaign | Tidak selaras | CRUD, PIC, deadline, komentar, activity log, dan Secure Link tersedia. Lifecycle, priority, objective, evidence, dan assignment Tim belum sesuai PRD. |
| Task | Tidak selaras | CRUD, deadline, visual submission, dan activity log tersedia. Assignee, priority, reminder, chat, completion report, review, serta Secure Link Task belum lengkap. |
| Performance Report | Belum tersedia | Report saat ini adalah analytical export, bukan Daily/Monthly Report yang memiliki lifecycle, konten, evidence, publishing, dan discussion. |
| Notification | Parsial | Infrastruktur notification log, queue, dan driver tersedia. Event operasional serta reminder berbasis priority belum lengkap. |
| Secure Link | Parsial | Campaign dan Promotion didukung. Task dan Report belum didukung; access log masih berupa waktu akses terakhir dan jumlah view. |
| Dashboard | Parsial | KPI, deadline, dan recent activity tersedia. Metrik Task/Report serta definisi indikator performa PRD belum lengkap. |

## 3. Gap per Requirement

### 3.1 Campaign

| ID | Status | Implementasi saat ini | Gap menuju target |
|---|---|---|---|
| CAM-01 | Parsial | Campaign menyimpan Brand, nama, deskripsi, tanggal, PIC, deadline, status, dan catatan. | Belum ada objective terpisah, priority, attachment/evidence, `created_by`, dan assignment beberapa Tim. |
| CAM-02 | Parsial | Filter status dan dashboard deadline tersedia. | Belum ada filter lifecycle final untuk Assigned, Waiting Review, Revision, Approved, serta indikator approaching deadline yang konsisten. |
| CAM-03 | Tidak selaras | Status dapat diperbarui dan perubahan status dicatat. Public review tersedia pada alur tertentu. | Belum ada transition guard dan endpoint tindakan khusus `submit review`, `approve`, serta `request revision` dengan actor dan catatan wajib. |
| CAM-04 | Parsial | Activity log, komentar, dan histori approval tertentu tersedia. | Assignment Tim, attachment, perubahan ownership, dan seluruh transition lifecycle belum menghasilkan audit contract yang seragam. |

Perbedaan lifecycle:

```text
Saat ini : Draft -> Waiting Approval -> Approved -> Running -> Finished
Target    : Draft -> Assigned -> In Progress -> Waiting Review -> Approved -> Completed
Revisi    : Waiting Review -> Revision -> In Progress
```

### 3.2 Task Management System

| ID | Status | Implementasi saat ini | Gap menuju target |
|---|---|---|---|
| TMS-01 | Parsial | Task wajib terhubung ke Campaign dan mendukung CRUD. | Belum ada assignee/PIC Task. Task mandiri berbasis Brand belum didukung. |
| TMS-02 | Belum tersedia | Deadline tersedia. | Priority Normal/Mid/Urgent, interval reminder configurable, approaching deadline, dan overdue policy belum tersedia. |
| TMS-03 | Belum tersedia | Belum ada relasi percakapan pada Task. | Diperlukan message, reply, attachment, sender, timestamp, dan unread state per Task. |
| TMS-04 | Parsial | Visual link/file, submitter, dan waktu submission tersedia. | Belum ada completion summary, detail pekerjaan, kumpulan evidence, transition Waiting Review, serta keputusan Completed/Revision oleh reviewer. |
| TMS-05 | Belum tersedia | Task dapat tampil sebagai bagian public review tertentu. | Belum ada lifecycle Secure Link yang dimiliki langsung oleh Task beserta revoke dan access log. |

Perbedaan lifecycle:

```text
Saat ini : NotStarted | InProgress | Completed | OnHold
Target    : Pending -> Assigned -> In Progress -> Waiting Review -> Completed
Revisi    : Waiting Review -> Revision -> In Progress
Jeda      : In Progress <-> On Hold
Batal     : Status nonterminal -> Cancelled
```

Enum Task saat ini memuat `Revision`, sedangkan request create/update tidak menerimanya. Ketidakkonsistenan ini harus diselesaikan bersamaan dengan implementasi lifecycle final.

### 3.3 Performance Management System

| ID | Status | Implementasi saat ini | Gap menuju target |
|---|---|---|---|
| PMS-01 | Belum tersedia | Endpoint report menghasilkan analytical export dari data operasional. | Diperlukan entity Daily/Monthly Report dengan Brand, periode, PIC, author, dan lifecycle. |
| PMS-02 | Belum tersedia | Export CSV/PDF/Excel tersedia. | Belum ada rich content, image collection, caption, ordering, copy-paste upload, dan attachment Report. |
| PMS-03 | Belum tersedia | Secure Link tersedia untuk Campaign dan Promotion. | Diperlukan approval, publishing, immutable published content, dan Secure Link Report. |
| PMS-04 | Belum tersedia | Komentar polymorphic tersedia untuk Campaign/Promotion. | Diperlukan discussion Report untuk Client dan user internal, termasuk reply dan attachment. |

Lifecycle target:

```text
Draft -> Waiting Review -> Approved -> Published
Waiting Review -> Revision -> Waiting Review
```

### 3.4 Cross-Module Services

| ID | Status | Implementasi saat ini | Gap menuju target |
|---|---|---|---|
| SYS-01 | Parsial | Notification service, log pengiriman, queue job, serta driver In-App/WhatsApp/Email tersedia. | Event Task/Campaign/Report dan reminder policy belum dipetakan lengkap. |
| SYS-02 | Parsial | Token, expiration, revoke, last access, dan view count tersedia. | Belum ada record per akses yang menyimpan waktu, resource, actor/session, dan IP bila diperlukan. |
| SYS-03 | Parsial | Activity log menyimpan actor, action, object, description, dan properties. | Kontrak previous/new value serta event assignment/review/publishing belum seragam di semua modul. |
| SYS-04 | Parsial | Campaign KPI, promotion KPI, deadline, approval rate, dan recent activity tersedia. | Task KPI, Report KPI, rumus performa, periode, dan drill-down belum lengkap. |

## 4. Keputusan Role, Ownership, dan Data Scope

Keputusan final berada pada PRD bagian 2 dan mengikuti `docs/RBAC.md`:

1. Role internal tetap `Super Admin`, `Admin`, dan `Tim`.
2. `PIC`, `creator`, `assignee`, `reviewer`, dan `publisher` adalah tanggung jawab pada object, bukan role baru.
3. Client bukan user internal. Client hanya memperoleh akses ke object tertentu melalui Secure Link.
4. Super Admin dan Admin memiliki cakupan data global.
5. Tim hanya memiliki cakupan efektif dari gabungan assignment Company dan Brand.
6. PIC atau assignee tidak boleh memperluas data scope. User tetap harus mempunyai permission dan cakupan Brand yang sesuai.
7. Approval, request revision, dan publishing memerlukan permission khusus dan hanya tersedia bagi Super Admin atau Admin.

Ownership final:

| Object | Scope utama | Pembuat | Penanggung jawab | Pelaksana |
|---|---|---|---|---|
| Campaign | Satu Brand | Super Admin/Admin/Tim dalam scope | Satu PIC Super Admin/Admin | Beberapa anggota Tim dalam scope |
| Task | Satu Brand; Campaign opsional dengan Brand yang sama | Super Admin/Admin/Tim dalam scope | Satu PIC Super Admin/Admin | Satu assignee Tim dalam scope |
| Report | Satu Brand | Super Admin/Admin/Tim dalam scope | Satu PIC Super Admin/Admin | Author adalah pembuat; reviewer/publisher Super Admin/Admin |
| Secure Link Client | Satu Campaign, Task, atau Report | Super Admin/Admin berwenang | Tidak mengubah ownership object | Client hanya pada resource yang ditunjuk token |

## 5. Keputusan Lifecycle

### Campaign

Status database/API: `draft`, `assigned`, `in_progress`, `waiting_review`, `revision`, `approved`, `completed`, dan `cancelled`.

### Task

Status database/API: `pending`, `assigned`, `in_progress`, `on_hold`, `waiting_review`, `revision`, `completed`, dan `cancelled`.

### Report

Status database/API: `draft`, `waiting_review`, `revision`, `approved`, dan `published`.

`Approaching Deadline`, `Overdue`, `Expired`, dan indikator waktu lain merupakan kondisi turunan. Nilai tersebut dihitung dari deadline/expiration dan status terminal, bukan disimpan sebagai workflow status.

Aturan final lain:

- Status UI menggunakan label yang mudah dibaca, sedangkan payload dan database memakai nilai canonical di atas.
- Report yang sudah `published` bersifat read-only; perubahan konten menghasilkan versi baru berstatus `draft`.
- Revoke atau expiration Secure Link tidak mengubah status Campaign, Task, atau Report.
- Seluruh transition menyimpan actor, waktu, status sebelum/sesudah, dan catatan yang diwajibkan transition.

## 6. Dampak Implementasi

| Lapisan | Perubahan yang diperlukan |
|---|---|
| Database | Migration status canonical, ownership/assignment, priority, completion report, Report, evidence, discussion, dan secure-link access log. |
| Backend | Enum, transition service/policy, validation, scoped query, action endpoint, notification event, dan audit contract. |
| Frontend | Form ownership, badge lifecycle, action sesuai next transition, review modal, report builder, chat, dan dashboard drill-down. |
| RBAC | Permission Campaign approval dipertahankan; permission Task review serta Report CRUD/review/publish perlu ditambahkan. |
| Migration data | Status lama harus dipetakan ke status canonical menggunakan migration terpisah dengan snapshot/rollback. |
| Testing | Setiap transition, actor, scope, manipulasi ID, audit entry, notification event, dan Secure Link harus memiliki feature test. |

## 7. Batas Tahap Ini

Dokumen ini belum mengubah database, enum, API, UI, atau data produksi. Implementasi dimulai setelah data model dan acceptance criteria setiap requirement disepakati.
