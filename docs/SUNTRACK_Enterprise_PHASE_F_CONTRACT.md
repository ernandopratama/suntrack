# SUNTRACK Enterprise - Fase F Contract

## Status

Kontraksi belum dimasukkan ke jalur migration otomatis. Audit read-only tersedia melalui:

```bash
php artisan suntrack:contract-audit \
  --observation-start="2026-09-05T00:00:00+07:00" \
  --observation-end="2026-09-12T00:00:00+07:00" \
  --backup-reference="/root/backup/suntrack-before-contract.dump"
```

Perintah menghasilkan `CONTRACT_READY` hanya jika:

- schema Fase E telah aktif;
- seluruh Campaign memiliki `created_by`;
- seluruh Task memiliki `brand_id` dan `created_by`;
- status Campaign dan Task sudah canonical;
- observasi produksi tercatat minimal tujuh hari;
- referensi backup tervalidasi diberikan.

## Kontraksi Setelah Audit Lulus

1. Catat jumlah row seluruh tabel yang terdampak.
2. Simpan dan verifikasi backup PostgreSQL.
3. Jalankan audit sampai menghasilkan `CONTRACT_READY`.
4. Buat migration kontraksi pada release terpisah untuk menjadikan `campaigns.created_by`, `tasks.brand_id`, dan `tasks.created_by` non-null.
5. Pantau satu release penuh sebelum menghapus compatibility alias `TaskResource.status`.
6. Ekspor `rbac_legacy_user_snapshots`, lalu hapus tabelnya pada release terakhir setelah rollback window ditutup.

Migration destruktif tidak boleh digabung dengan release Fase E.
