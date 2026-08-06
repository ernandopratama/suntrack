# SunTrack — Backup, Retention & Disaster Recovery Procedure (DRP)

This document establishes the operational procedures, retention schedules, and disaster recovery runbooks required to ensure business continuity and data preservation for SunTrack Enterprise.

---

## 1. Automated Backup Architecture

SunTrack executes automated daily backups of primary database schemas, relational records, and system settings without manual intervention.

### Backup Schedule & Execution (`routes/console.php`):
- **Command:** `php artisan suntrack:backup-db` (`App\Console\Commands\BackupDatabaseCommand`).
- **Schedule:** Runs daily at **01:00 AM server time** via the containerized `scheduler` service (`suntrack-scheduler`).
- **Storage Target:** Managed dynamically via `StorageService` (`LocalDriver`, `S3Driver`, or `GoogleDriveDriver` based on system configuration).
- **Archive Format:** Compressed JSON/SQL snapshot (`backups/suntrack_backup_YYYY_MM_DD_HHmmss.json`) capturing all tables (`users`, `campaigns`, `promotions`, `products`, `variants`, `promotion_variant`, `secure_links`, `approval_histories`, `comments`, `system_settings`, `login_histories`, `activity_logs`).
- **Audit Logging:** Successful backup creations and durations (in milliseconds) are recorded in `ActivityLogger` under `System:Backup`.

---

## 2. Retention Policy & Storage Management

To balance storage costs with compliance requirements, SunTrack enforces a tiered backup retention policy:
1.  **Daily Snapshots (Last 30 Days):** All daily backup files are retained in primary storage (`S3` or `Google Drive`) for 30 consecutive days.
2.  **Monthly Archives (Last 12 Months):** On the 1st of each month, the daily snapshot is tagged as a monthly archive and retained for 1 year for financial and promotion audit compliance.
3.  **Automated Cleanup:** The scheduled background job (`CleanTemporaryFilesJob`) and retention cron rules evaluate storage archives, automatically purging expired snapshots older than the 30-day/12-month threshold.

---

## 3. Disaster Recovery Procedure (DRP) & Restore Runbooks

In the event of critical hardware failure, database corruption, or unintended data deletion, system administrators MUST follow these containerized restore runbooks.

### A. Database Restore Runbook (Container-First)
To restore a database snapshot into a running production MySQL container (`suntrack-mysql`):

1.  **Locate the Desired Snapshot:** Identify the backup file from storage (e.g., `suntrack_backup_2026_07_27_010000.json` or `.sql`).
2.  **Transfer Snapshot to Host (if stored in S3/Google Drive):**
    ```bash
    # Example using AWS CLI or downloading to server /tmp directory
    aws s3 cp s3://suntrack-backups/backups/suntrack_backup_2026_07_27_010000.json ./restore_backup.json
    ```
3.  **Place Container Stack into Maintenance Mode:** Prevent web traffic during restore:
    ```bash
    docker compose exec app php artisan down --message="System undergoing scheduled disaster recovery restoration."
    ```
4.  **Execute Database Restoration Inside Container:** If restoring a native SQL dump:
    ```bash
    cat ./restore_backup.sql | docker compose exec -i mysql mysql -u root -proot suntrack_prod
    ```
    If restoring from a JSON snapshot archive, run the automated import command (or seed via artisan):
    ```bash
    docker compose exec app php artisan suntrack:restore-db --file=backups/suntrack_backup_2026_07_27_010000.json
    ```
5.  **Clear Caches & Re-enable Web Traffic:**
    ```bash
    docker compose exec app php artisan optimize:clear
    docker compose exec app php artisan up
    ```

### B. Media & Attachment Restore Runbook
To restore uploaded campaign banners, brand review attachments, or exported PDF reports:
1. Ensure `FILESYSTEM_DISK` in `.env` is pointed to the backup bucket (`s3` or `google_drive`).
2. Run the storage synchronization command inside the container:
    ```bash
    docker compose exec app php artisan suntrack:sync-storage --source=backup_bucket --target=local_public
    ```

---

## 4. Backup Verification & Staging Drills

A backup is only as reliable as its restoration process. To guarantee Disaster Recovery readiness:
- **Automated CI/CD Verification:** The GitHub Actions CI/CD pipeline (`.github/workflows/ci.yml`) executes automated database migration and seeding verification on every pull request.
- **Monthly Staging Drill:** On the 1st Tuesday of every month, operational administrators must download the latest S3/Google Drive production backup, spin up an isolated staging Docker container stack (`docker compose -f docker-compose.staging.yml up -d`), and execute the Database Restore Runbook to verify zero data corruption.
