# SUNTRACK Enterprise
## Product Requirements Document (PRD)

**Monitoring & Approval Campaign / Task Management System / Performance Management System**

> **Tujuan dokumen**
> Mendefinisikan kebutuhan produk SUNTRACK untuk pengelolaan campaign, task operasional, report client, secure link, notification, chat, audit trail, dan dashboard monitoring dalam satu alur kerja terintegrasi.

| Informasi | Nilai |
|---|---|
| **Versi** | 1.0 |
| **Tanggal** | 05 September 2026 |
| **Status** | Fase E Implemented - Fase F Observation Window |
| **Dokumen** | Internal Produk |
| **Gap Analysis** | `docs/SUNTRACK_Enterprise_PRD_GAP_ANALYSIS.md` |

---

## Daftar Isi

1. [Ringkasan Produk](#1-ringkasan-produk)
2. [Ruang Lingkup & Peran Pengguna](#2-ruang-lingkup--peran-pengguna)
3. [Monitoring & Approval Campaign](#3-monitoring--approval-campaign)
4. [Task Management System (TMS)](#4-task-management-system-tms)
5. [Performance Management System (PMS)](#5-performance-management-system-pms)
6. [Cross-Module Services](#6-cross-module-services)
7. [Dashboard Monitoring & Performance Indicators](#7-dashboard-monitoring--performance-indicators)
8. [Alur Sistem End-to-End & Requirement Index](#8-alur-sistem-end-to-end--requirement-index)

---

# 1. Ringkasan Produk

SUNTRACK diposisikan sebagai pusat koordinasi operasional antara **Client, Super Admin, Admin/PIC, dan Tim**.

### Campaign
Mengelola campaign dari pembuatan, assignment, progress, review, revisi, approval, hingga completion.

### Task Management System
Mengelola pekerjaan operasional, prioritas, reminder, chat, completion report, dan bukti pekerjaan.

### Performance Management System
Menyusun daily/monthly report dan membagikannya kepada Client melalui Secure Link.

### Cross-Module Services
Notification, WhatsApp reminder, activity log, audit trail, chat, dashboard, dan secure access.

> **Prinsip produk**
> Setiap pekerjaan harus dapat ditelusuri: siapa yang membuat, siapa yang bertanggung jawab, status terkini, bukti hasil, riwayat perubahan, dan komunikasi terkait.

---

# 2. Ruang Lingkup & Peran Pengguna

## 2.1 Scope Utama

PRD ini berfokus pada proses operasional:

- Campaign
- Task
- Report
- Monitoring
- Approval
- Komunikasi
- Penyampaian bukti hasil kepada Client

## 2.2 Peran Pengguna

SUNTRACK hanya memiliki tiga role internal. PIC, assignee, reviewer, dan publisher merupakan tanggung jawab pada object, bukan role tambahan.

### Super Admin

Memiliki seluruh permission dan cakupan data global. Super Admin dapat:

- Mengelola seluruh user, role, permission, dan assignment Company/Brand.
- Mengelola seluruh data operasional.
- Melakukan review, approval, completion, publishing, dan revoke Secure Link.
- Mengakses seluruh Activity Log, audit, export, dan monitoring sistem.

### Admin

Memiliki cakupan data global untuk seluruh Company dan Brand. Admin dapat:

- Menerjemahkan kebutuhan Client menjadi campaign atau task.
- Membuat dan mengedit user Tim.
- Mengatur permission dan assignment Company/Brand user Tim.
- Mengelola Company, Brand, Campaign, Task, Report, dan data operasional terkait.
- Melakukan assignment, monitoring, review, approval, completion, publishing, dan revoke Secure Link.
- Menyampaikan hasil kepada Client.

Admin tidak dapat mengelola Super Admin, Admin lain, dirinya sendiri, atau menghapus user.

### Tim

Memiliki akses operasional dalam cakupan Company dan Brand yang ditugaskan. Tim dapat:

- Membuat dan mengelola Campaign, Task, dan Report dalam cakupan efektifnya sesuai permission.
- Menjadi anggota Campaign atau assignee Task serta menjadi author Report.
- Memperbarui status.
- Berkomunikasi dengan Admin.
- Mengirim hasil untuk review.
- Melampirkan bukti pekerjaan.

Tim tidak dapat mengelola user, role, permission, assignment scope, Company, atau Brand. Tim tidak dapat melakukan approval akhir atau publishing.

### Client

Client bukan user internal dan tidak memiliki role pada SUNTRACK. Client dapat:

- Mengakses satu object yang dibagikan melalui Secure Link yang valid.
- Membaca report.
- Melihat bukti pekerjaan.
- Memberikan komentar atau feedback pada area yang disediakan.

Secure Link tidak memberikan akses ke object lain dalam Company atau Brand yang sama.

### System

System adalah actor otomatis yang bertanggung jawab terhadap:

- Reminder.
- Notification.
- Secure access.
- Activity log.
- Audit trail.
- Indikator dashboard.

## 2.3 Ownership Object

Hierarki ownership utama:

```text
Company
  -> Brand
       -> Campaign
            -> Task terkait Campaign
       -> Task mandiri
       -> Daily / Monthly Report
```

Aturan ownership:

- Setiap Campaign dan Report wajib dimiliki satu Brand.
- Task wajib dimiliki satu Brand dan dapat terhubung ke satu Campaign. Jika Campaign dipilih, Brand Task harus sama dengan Brand Campaign.
- Setiap object menyimpan `created_by` sebagai pembuat yang tidak berubah ketika PIC atau assignee diganti.
- Campaign memiliki satu PIC utama dari role Super Admin/Admin dan dapat memiliki beberapa anggota Tim.
- Task memiliki satu PIC utama dari role Super Admin/Admin serta satu assignee utama dari role Tim. PIC dan assignee dapat diganti oleh Super Admin/Admin dan setiap perubahan wajib diaudit.
- Report memiliki satu author dan satu PIC utama. Author dapat berupa Super Admin, Admin, atau Tim dalam scope Brand terkait; PIC harus berasal dari role Super Admin/Admin.
- Reviewer dan publisher harus merupakan Super Admin atau Admin yang memiliki permission tindakan terkait.
- PIC atau assignee menentukan tanggung jawab, notification, dan tampilan workload. Ownership tidak menggantikan permission atau data scope.

## 2.4 Data Scope

- Super Admin dan Admin dapat mengakses seluruh Company, Brand, dan object operasional.
- Cakupan efektif Tim adalah gabungan seluruh Brand dari Company assignment dan Brand assignment langsung.
- Tim dapat mengakses Campaign, Task, Report, evidence, discussion, Activity Log, dan export jika Brand object termasuk dalam cakupan efektifnya.
- Assignment Brand langsung hanya membuka Brand tersebut dan Company induknya sebagai referensi; Brand lain dalam Company yang sama tetap tertutup.
- Assignment Company otomatis mencakup Brand baru yang dibuat di bawah Company tersebut.
- PIC, membership Campaign, atau assignee Task tidak boleh memperluas scope. Assignment object hanya valid bila user memiliki akses ke Brand object.
- Filter, pencarian, dashboard, export, relasi, dan pilihan form wajib menggunakan scope yang sama.
- Client melalui Secure Link hanya dapat mengakses object, evidence, dan discussion yang terikat pada token tersebut.

Spesifikasi permission dan perhitungan scope mengikuti `docs/RBAC.md`.

## 2.5 Alur Kolaborasi Dasar

```text
CLIENT
  ↓
ADMIN / PIC
  ↓
CAMPAIGN / TASK
  ↓
TIM
  ↓
REVIEW
  ↓
SECURE LINK
```

- **Client → Admin:** Permintaan, kebutuhan campaign, pekerjaan, atau laporan.
- **Admin → Tim:** Assignment dengan konteks, priority, deadline, dan attachment.
- **Tim → Admin:** Progress, komunikasi, completion report, dan bukti pekerjaan.
- **Admin → Client:** Hasil yang sudah direview melalui Secure Link atau report terpublikasi.

## 2.6 Prinsip Pengalaman Pengguna

- Status dan next action harus terlihat jelas pada setiap halaman detail.
- Informasi prioritas, deadline, assignee, dan brand harus mudah dipindai.
- Riwayat komunikasi dan bukti pekerjaan tetap berada dalam konteks objek yang sama.
- Tampilan Client melalui Secure Link harus ringkas, responsive, dan mudah dipahami.

---

# 3. Monitoring & Approval Campaign

Mengelola lifecycle campaign dari draft sampai completed dengan review dan audit trail.

## 3.1 Tujuan Modul

- Membuat dan mengelola campaign berdasarkan Brand dan PIC terkait.
- Memantau progress, deadline, status review, revision, dan approval.
- Menyimpan hasil pekerjaan dan riwayat perubahan status untuk kebutuhan audit.

## 3.2 Data Utama Campaign

### Identitas
- Nama campaign.
- Brand.
- Objective.
- Deskripsi.
- Attachment pendukung.

### Ownership
- Satu PIC dari role Super Admin/Admin.
- Satu atau beberapa anggota Tim yang ditugaskan.

### Timeline
- Tanggal mulai.
- Deadline.
- Indikator approaching deadline.
- Indikator overdue.

### Control
- Priority.
- Catatan.
- Current status.
- Approval/revision notes.
- Activity history.

## 3.3 Status Lifecycle

Nilai status canonical pada database dan API menggunakan `snake_case`: `draft`, `assigned`, `in_progress`, `waiting_review`, `revision`, `approved`, `completed`, dan `cancelled`. Antarmuka menampilkan label yang mudah dibaca.

```text
DRAFT
  -> ASSIGNED
  -> IN PROGRESS
  -> WAITING REVIEW
  -> APPROVED
  -> COMPLETED
```

Jalur revisi:

```text
WAITING REVIEW
  -> REVISION
  -> IN PROGRESS
  -> WAITING REVIEW
```

Jalur pembatalan:

```text
DRAFT / ASSIGNED / IN PROGRESS / WAITING REVIEW / REVISION
  -> CANCELLED
```

Aturan transition:

| Dari | Ke | Actor | Prasyarat |
|---|---|---|---|
| Draft | Assigned | Super Admin/Admin | Brand, PIC, minimal satu anggota Tim, dan deadline terisi. |
| Assigned | In Progress | Super Admin/Admin/anggota Tim | Tim harus terdaftar sebagai anggota Campaign; actor memiliki permission update dan scope Brand. |
| In Progress | Waiting Review | Super Admin/Admin/anggota Tim | Tim harus terdaftar sebagai anggota Campaign; hasil kerja atau evidence minimum telah dikirim. |
| Waiting Review | Revision | Super Admin/Admin | Catatan revisi wajib diisi. |
| Waiting Review | Approved | Super Admin/Admin | Actor memiliki permission approval. |
| Revision | In Progress | Super Admin/Admin/anggota Tim | Tim harus terdaftar sebagai anggota Campaign; revisi mulai dikerjakan. |
| Approved | Completed | Super Admin/Admin | Hasil akhir dan delivery Client telah dikonfirmasi. |
| Status nonterminal | Cancelled | Super Admin/Admin | Alasan pembatalan wajib diisi. |

`Approaching Deadline` dan `Overdue` merupakan kondisi turunan:

- Approaching Deadline berlaku ketika deadline berada dalam interval konfigurasi dan status belum `Completed` atau `Cancelled`.
- Overdue berlaku ketika waktu sekarang melewati deadline dan status belum `Completed` atau `Cancelled`.
- Kondisi turunan tidak mengganti status workflow.

## 3.4 Approval & Activity Log

- Tim mengirim hasil pekerjaan dan mengubah status menjadi `Waiting Review`.
- Admin melakukan `Approve` atau `Request Revision`.
- Setiap tindakan menyimpan:
  - Actor.
  - Timestamp.
  - Status sebelum.
  - Status sesudah.
  - Catatan.
  - Attachment jika ada.
- Activity log mencatat:
  - Pembuatan.
  - Edit.
  - Assignment.
  - Perubahan PIC.
  - Perubahan status.
  - Penambahan file.
  - Revisi.
  - Approval.
  - Completion.

## 3.5 Requirement Campaign

| ID | Area | Requirement |
|---|---|---|
| **CAM-01** | Campaign Management | User dengan permission `campaign.create` dapat membuat Campaign dalam scope Brand; Super Admin/Admin menetapkan PIC dan anggota Tim sebelum status `assigned`. |
| **CAM-02** | Monitoring | Admin dapat memfilter campaign aktif, selesai, approaching deadline, overdue, waiting review, dan revision. |
| **CAM-03** | Approval | Admin dapat approve atau meminta revision dengan catatan yang tersimpan. |
| **CAM-04** | Audit | Seluruh perubahan penting campaign tercatat dalam activity history. |

---

# 4. Task Management System (TMS)

Mengelola pekerjaan operasional dari assignment hingga completion report dan penyampaian hasil.

## 4.1 Tujuan Modul

- Admin atau PIC mencatat kebutuhan Client sebagai Task dan menugaskannya kepada satu assignee Tim.
- Tim menyelesaikan Task, memperbarui status, dan mengirim laporan singkat beserta screenshot/bukti.
- Admin memonitor progress dan mereview hasil sebelum disampaikan kepada Client.

## 4.2 Form Pembuatan Task

### Task Identity
- Judul Task.
- Brand.
- Deskripsi/instruksi.

### Assignment
- PIC utama dari role Super Admin/Admin.
- Satu assignee utama dari role Tim.
- Campaign opsional; jika dipilih, Brand Task wajib sama dengan Brand Campaign.

### Control
- Priority.
- Deadline.
- Status awal.
- Catatan tambahan.

### Evidence Input
- Attachment.
- Dokumen pendukung yang diperlukan Tim.

## 4.3 Priority & Reminder Policy

### Normal
Pekerjaan dengan urgency rendah. Interval reminder lebih longgar.

### Mid
Pekerjaan dengan urgency menengah. Reminder lebih sering daripada Normal.

### Urgent
Pekerjaan yang membutuhkan penanganan segera. Reminder menggunakan interval paling pendek.

### Configurable
Interval reminder disimpan sebagai konfigurasi sistem agar dapat diubah tanpa perubahan source code.

## 4.4 Status Task

Nilai status canonical pada database dan API menggunakan `snake_case`: `pending`, `assigned`, `in_progress`, `on_hold`, `waiting_review`, `revision`, `completed`, dan `cancelled`.

```text
PENDING
  -> ASSIGNED
  -> IN PROGRESS
  -> WAITING REVIEW
  -> COMPLETED
```

Jalur jeda dan revisi:

```text
IN PROGRESS <-> ON HOLD

WAITING REVIEW
  -> REVISION
  -> IN PROGRESS
  -> WAITING REVIEW
```

Jalur pembatalan:

```text
PENDING / ASSIGNED / IN PROGRESS / ON HOLD / WAITING REVIEW / REVISION
  -> CANCELLED
```

Aturan transition:

| Dari | Ke | Actor | Prasyarat |
|---|---|---|---|
| Pending | Assigned | Super Admin/Admin | Brand, PIC, assignee Tim, priority, instruksi, dan deadline terisi. |
| Assigned | In Progress | Super Admin/Admin/assignee Tim | Actor memiliki permission update dan scope Brand. |
| In Progress | On Hold | Super Admin/Admin/assignee Tim | Alasan jeda wajib diisi. |
| On Hold | In Progress | Super Admin/Admin/assignee Tim | Catatan kelanjutan disimpan. |
| In Progress | Waiting Review | Super Admin/Admin/assignee Tim | Completion summary dan evidence wajib telah dikirim. |
| Waiting Review | Revision | Super Admin/Admin | Catatan revisi wajib diisi. |
| Waiting Review | Completed | Super Admin/Admin | Hasil telah diverifikasi dan actor memiliki permission review. |
| Revision | In Progress | Super Admin/Admin/assignee Tim | Revisi mulai dikerjakan. |
| Status nonterminal | Cancelled | Super Admin/Admin | Alasan pembatalan wajib diisi. |

`Approaching Deadline` dan `Overdue` dihitung dari deadline untuk Task yang belum `completed` atau `cancelled`. Keduanya bukan status workflow.

## 4.5 Reminder & Notification

Reminder dapat dikirim ketika:

- Task baru diberikan.
- Task belum dibuka.
- Task belum dikerjakan.
- Task mendekati deadline.
- Task overdue.
- Priority berubah.
- Ada chat baru.
- Ada revision request.

Channel utama:

- In-App Notification.
- WhatsApp.

Isi minimum reminder:

- Judul Task.
- Brand.
- Priority.
- Deadline.
- Status.
- Link menuju Task.

Reminder berhenti ketika Task mencapai status yang tidak lagi membutuhkan pengerjaan, seperti:

- `Waiting Review`
- `Completed`
- `Cancelled`

Task `On Hold` tidak menerima reminder pengerjaan reguler, tetapi dapat menerima reminder evaluasi jeda sesuai konfigurasi.

### WhatsApp Reminder Flow

```text
SUNTRACK
  ↓
Notification Service
  ↓
WhatsApp
  ↓
Assignee Tim dan PIC Task
```

## 4.6 Task Chat

Setiap Task memiliki chat aktif antara Admin dan Tim agar diskusi tidak tercampur dengan Task lain.

Konten yang didukung:

- Text message.
- Image.
- Attachment.
- Reply message.
- Timestamp.
- Sender identity.
- Unread indicator.

## 4.7 Completion Report

Tim wajib:

- Mengisi ringkasan pekerjaan.
- Menjelaskan apa saja yang telah dilakukan.
- Melampirkan screenshot/bukti hasil.
- Melampirkan attachment tambahan bila diperlukan.

Setelah completion report dikirim:

```text
IN PROGRESS
  ↓
WAITING REVIEW
```

Admin dapat:

```text
WAITING REVIEW
  ├──→ COMPLETED
  └──→ REVISION
```

## 4.8 Secure Link Task

Admin dapat membuat Secure Link setelah Task siap disampaikan kepada Client.

Secure Link menampilkan:

- Brand.
- Judul Task.
- Ringkasan.
- Hasil pekerjaan.
- Screenshot.
- Attachment.
- Tanggal selesai.
- PIC.

Secure Link menggunakan:

- Unique token.
- Expiration.
- Status aktif/nonaktif.
- Access log.
- Opsi revoke.

## 4.9 Requirement TMS

| ID | Area | Requirement |
|---|---|---|
| **TMS-01** | Assignment | User dengan permission `task.create` dapat membuat Task dalam scope Brand; Super Admin/Admin menetapkan PIC dan assignee Tim sebelum status `assigned`. |
| **TMS-02** | Priority | Task memiliki Normal, Mid, dan Urgent dengan reminder interval configurable. |
| **TMS-03** | Chat | Setiap Task memiliki ruang chat internal antara Admin dan Tim. |
| **TMS-04** | Completion | Tim mengirim completion report dan bukti; Admin melakukan review. |
| **TMS-05** | Client Delivery | Admin dapat membagikan hasil Task menggunakan Secure Link. |

---

# 5. Performance Management System (PMS)

Membuat daily/monthly report yang mudah dipahami dan dapat dibagikan kepada Client melalui Secure Link.

## 5.1 Jenis Report

### Daily Report
Laporan aktivitas atau performa harian Brand.

### Monthly Report
Rangkuman performa dan aktivitas Brand dalam periode bulanan.

## 5.2 Form Report

- **Brand:** Searchable Dropdown untuk memilih Brand.
- **Author:** Otomatis dari user pembuat dan tidak berubah ketika PIC diganti.
- **PIC:** Satu user dengan role Super Admin/Admin.
- **Jenis Report:** Daily Report atau Monthly Report.
- **Judul Report:** Judul utama yang ditampilkan kepada Client.
- **Report Content:** Rich Text Editor untuk heading, paragraph, bold, italic, list, link, dan table bila diperlukan.
- **Report Images:** Upload, drag & drop, copy-paste, caption, dan pengaturan urutan gambar.
- **Attachment:** Dokumen tambahan apabila diperlukan.

## 5.3 Status Report

Nilai status canonical pada database dan API menggunakan `snake_case`: `draft`, `waiting_review`, `revision`, `approved`, dan `published`.

```text
DRAFT
  -> WAITING REVIEW
  -> APPROVED
  -> PUBLISHED
```

Jalur revisi:

```text
WAITING REVIEW
  -> REVISION
  -> WAITING REVIEW
```

Aturan transition:

| Dari | Ke | Actor | Prasyarat |
|---|---|---|---|
| Draft | Waiting Review | Super Admin/Admin/author Tim | Brand, jenis, periode, judul, ringkasan, dan konten utama terisi. |
| Waiting Review | Revision | Super Admin/Admin | Catatan revisi wajib diisi. |
| Waiting Review | Approved | Super Admin/Admin | Konten dan evidence telah diverifikasi. |
| Revision | Waiting Review | Super Admin/Admin/author Tim | Perbaikan selesai dan dikirim ulang untuk review. |
| Approved | Published | Super Admin/Admin | Actor memiliki permission publish dan publish date dicatat. |

Report `published` bersifat read-only. Perubahan konten setelah publikasi menghasilkan versi Report baru berstatus `draft`. Revoke atau expiration Secure Link tidak mengubah status Report.

> **Aturan akses**
> Client hanya dapat mengakses Report berstatus `published` melalui Secure Link yang aktif dan belum kedaluwarsa.

## 5.4 Tampilan Report Client

Tampilan report harus:

- Minimalis.
- Responsive.
- Mudah dibaca pada desktop dan mobile.
- Memiliki struktur informasi yang jelas.

Struktur informasi:

1. Brand.
2. Judul.
3. Jenis report.
4. Periode.
5. Publish date.
6. PIC.
7. Executive summary.
8. Detail report.
9. Evidence.
10. Attachment.
11. Discussion/chat.

Ketentuan tambahan:

- Gambar dapat diperbesar.
- Setiap gambar memiliki caption yang jelas.
- Branding SUNTRACK tetap tersedia tanpa mengganggu fokus terhadap isi report.

## 5.5 Report Discussion

Client dapat:

- Memberikan komentar.
- Memberikan pertanyaan.
- Memberikan feedback.
- Melakukan reply.

Super Admin, Admin/PIC, dan author Tim dalam Brand terkait dapat:

- Memberikan klarifikasi.
- Mengirim gambar.
- Mengirim attachment.

Percakapan selalu terikat pada Report terkait.

## 5.6 Requirement PMS

| ID | Area | Requirement |
|---|---|---|
| **PMS-01** | Report Builder | Admin/Tim dapat membuat Daily dan Monthly Report berdasarkan Brand. |
| **PMS-02** | Rich Content | Report mendukung rich text, image upload/copy-paste, caption, sorting, dan attachment. |
| **PMS-03** | Publishing | Report dapat dipublikasikan dan dibagikan melalui Secure Link. |
| **PMS-04** | Client Discussion | Report memiliki ruang diskusi Client dengan Super Admin, Admin/PIC, dan author Tim sesuai data scope. |

---

# 6. Cross-Module Services

## 6.1 Notification System

### Task Events
- Task baru.
- Status berubah.
- Revision.
- Approaching deadline.
- Overdue.
- Chat baru.

### Campaign Events
- Waiting review.
- Revision request.
- Approval.
- Completion.

### Report Events
- Report dipublikasikan.
- Ada komentar Client.

### Channels
Tahap awal memprioritaskan:

- In-App Notification.
- WhatsApp.

Email dapat menjadi channel pengembangan.

## 6.2 Secure Link Standard

Secure Link harus memiliki:

- Token unik yang sulit ditebak.
- Expiration.
- Status aktif/nonaktif.
- Revoke link kapan pun oleh user berwenang.
- Access log untuk mengetahui waktu akses.
- Halaman Client yang tidak mewajibkan akun SUNTRACK untuk use case yang memang dibagikan secara eksternal.

## 6.3 Activity Log & Audit Trail

Data yang dicatat:

- User.
- Role.
- Action.
- Module.
- Object ID.
- Previous Value.
- New Value.
- Date/time.
- IP address apabila diperlukan.

Audit trail harus dapat membantu menelusuri:

- Assignment.
- Perubahan status.
- Revision.
- Approval.
- Completion.

### Contoh Audit Trail

```text
10:35 Admin A assigned Task #102 to Tim B
11:12 Tim B changed Assigned → In Progress
13:42 Completion report submitted
13:55 Admin A approved Task #102
```

## 6.4 Prinsip Keamanan & Kontrol

- Aksi approval, revoke, assignment, dan perubahan data penting mengikuti permission/RBAC.
- Secure Link memiliki lifecycle dan access log yang dapat diaudit.
- File dan evidence tetap terikat pada Campaign, Task, atau Report asalnya.
- Riwayat perubahan penting tidak dihapus dari sudut pandang audit operasional.

---

# 7. Dashboard Monitoring & Performance Indicators

## 7.1 Campaign Monitoring

### Active Campaign
Jumlah campaign aktif dan distribusi status.

### Review & Revision
Campaign yang menunggu review atau sedang revision.

### Deadline
Campaign approaching deadline dan overdue.

### Completion
Campaign yang selesai dalam periode yang dipilih.

## 7.2 Task Monitoring

### Operational Count
- Total.
- Pending.
- In Progress.
- Waiting Review.
- Completed.

### Priority
Jumlah Task berdasarkan:

- Urgent.
- Mid.
- Normal.

### Overdue
Task yang melewati deadline.

### Filter
Task dapat difilter berdasarkan:

- Brand.
- PIC atau assignee Tim.
- Status.
- Priority.

## 7.3 Report Monitoring

### Daily / Monthly
Jumlah report berdasarkan jenis dan periode.

### Publishing Status
- Draft.
- Published.

## 7.4 Performance Indicators

- Task Completion Rate.
- Average Completion Time.
- Overdue Rate.
- Jumlah pekerjaan per PIC dan assignee Tim.
- Jumlah pekerjaan per Brand.
- Campaign completion.
- Aktivitas Tim.

> **Dashboard principle**
> Dashboard digunakan untuk monitoring dan decision support. Setiap angka utama harus dapat ditelusuri ke daftar data yang membentuk metrik tersebut.

---

# 8. Alur Sistem End-to-End & Requirement Index

## 8.1 Alur End-to-End SUNTRACK

### 1. Client
Mengirim kebutuhan, campaign request, task, atau kebutuhan report.

### 2. Admin / PIC
Mencatat kebutuhan, memilih Brand, menentukan priority/timeline, dan melakukan assignment.

### 3. Campaign / Task
Menjadi unit kerja yang dimonitor status, progress, komunikasi, dan deadline.

### 4. Tim
Mengerjakan, memperbarui status, berkomunikasi, dan mengirim evidence/completion report.

### 5. Review / Approval
Admin meninjau hasil, approve, atau meminta revision.

### 6. Report / Secure Link
Hasil dipublikasikan atau dibagikan kepada Client.

### 7. Client Feedback
Client membaca hasil dan dapat memberikan feedback/discussion pada konteks yang tersedia.

### Alur Ringkas

```text
CLIENT
  ↓
ADMIN / PIC
  ↓
CAMPAIGN / TASK
  ↓
TIM
  ↓
REVIEW / APPROVAL
  ↓
REPORT / SECURE LINK
  ↓
CLIENT FEEDBACK
```

## 8.2 Requirement Index

| ID | Area | Requirement |
|---|---|---|
| **CAM-01..04** | Campaign | Campaign management, monitoring, approval, revision, dan audit trail. |
| **TMS-01..05** | Task | Assignment, priority/reminder, chat, completion report, dan Secure Link. |
| **PMS-01..04** | Report | Report builder, rich content, publishing, Secure Link, dan client discussion. |
| **SYS-01** | Notification | In-App + WhatsApp notification untuk event operasional utama. |
| **SYS-02** | Secure Link | Token, expiration, revoke, active status, dan access log. |
| **SYS-03** | Audit | Activity log untuk perubahan penting dan approval lifecycle. |
| **SYS-04** | Dashboard | Monitoring campaign, task, report, deadline, dan performance indicators. |

---

# Definition of Done

Requirement dianggap siap diterjemahkan ke desain teknis ketika:

- Role dan permission sudah disepakati.
- Status lifecycle setiap modul sudah disepakati.
- Data minimum setiap modul sudah ditentukan.
- Event notification sudah ditentukan.
- Acceptance behavior untuk setiap module sudah disepakati.

Status keputusan tahap ini:

| Area | Status |
|---|---|
| Gap analysis terhadap baseline `32cb84c` | Final |
| Role, ownership, dan data scope | Final |
| Lifecycle Campaign, Task, dan Report | Final |
| Data model core Campaign, Task, dan Report | Final dan diimplementasikan |
| Acceptance criteria core | Final dan teruji |
| Event notification dan collaboration services | Fase E selesai dan teruji |
| Kontraksi schema legacy | Guard tersedia; menunggu observasi produksi minimal tujuh hari dan backup tervalidasi |

---

## Document Control

**PRD v1.0 - SUNTRACK Enterprise - 05 September 2026**
