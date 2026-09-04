# SunTrack - Runbook Deployment Webuzo

Dokumen ini adalah sumber utama deployment produksi SunTrack ke VPS Webuzo. Target produksi menggunakan PostgreSQL dan Redis tanpa Docker Compose.

## 1. Arsitektur produksi

- Domain: `https://suntrack.sunriseadsacademy.com`
- Project: `/home/sunrise/suntrack-app`
- Document root: `/home/sunrise/suntrack-app/public`
- Web: Nginx Webuzo ke Apache, lalu PHP-FPM 8.4
- Database: PostgreSQL pada `127.0.0.1:5432`
- Cache, session, dan queue: Redis pada `127.0.0.1:6379`
- Queue worker: `suntrack-queue.service`
- Scheduler: `suntrack-scheduler.timer`

PHP 8.4.1 atau lebih baru diperlukan oleh dependency Symfony yang terkunci di `composer.lock`.

## 2. Persiapan server satu kali

### 2.1 PostgreSQL

Buat role dan database melalui Terminal admin Webuzo. Ganti password sebelum menjalankan perintah.

```bash
sudo -u postgres psql -c "CREATE USER sunrise_nando45 WITH PASSWORD 'CHANGE_ME';"
sudo -u postgres psql -c "CREATE DATABASE sunrise_suntrack OWNER sunrise_nando45;"
```

Database yang sudah ada tidak perlu dibuat ulang.

### 2.2 PHP 8.4

Aktifkan ekstensi berikut di `/usr/local/apps/php84/etc/php.d/extra.ini`:

```ini
extension=pgsql.so
extension=pdo_pgsql.so
extension=igbinary.so
extension=redis.so
session.gc_divisor=100
```

Pastikan ekstensi runtime tersedia:

```bash
/usr/local/apps/php84/bin/php -m | grep -E 'bcmath|gd|igbinary|intl|mbstring|pdo_pgsql|pgsql|redis|zip'
systemctl restart php-fpm84.service
```

Jangan gunakan PHP 8.5 pada VPS ini.

### 2.3 Clone dan environment

```bash
sudo -u sunrise git clone https://github.com/ernandopratama/suntrack.git /home/sunrise/suntrack-app
cd /home/sunrise/suntrack-app
cp .env.production.example .env
chown sunrise:sunrise .env
chmod 600 .env
```

Isi `APP_KEY`, `DB_PASSWORD`, dan konfigurasi layanan produksi. Nilai Redis harus dipisah:

```ini
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Untuk instalasi pertama, `APP_KEY` dapat dibuat setelah Composer selesai:

```bash
sudo -u sunrise /usr/local/apps/php84/bin/php /usr/local/bin/composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
sudo -u sunrise /usr/local/apps/php84/bin/php artisan key:generate --force
sudo -u sunrise npm ci
sudo -u sunrise npm run build
sudo -u sunrise /usr/local/apps/php84/bin/php artisan migrate --force
sudo -u sunrise /usr/local/apps/php84/bin/php artisan db:seed --class=ProductionSeeder --force
sudo -u sunrise /usr/local/apps/php84/bin/php artisan storage:link
sudo -u sunrise /usr/local/apps/php84/bin/php artisan optimize
```

Jangan menjalankan `key:generate` lagi pada deployment berikutnya.

### 2.4 Apache dan PHP-FPM

Cadangkan konfigurasi domain yang aktif, lalu pasang template repository:

```bash
cp /var/webuzo-data/apache2/custom/domains/suntrack.sunriseadsacademy.com.conf /var/webuzo-data/apache2/custom/domains/suntrack.sunriseadsacademy.com.conf.backup
install -m 0644 deploy/webuzo/apache.conf.example /var/webuzo-data/apache2/custom/domains/suntrack.sunriseadsacademy.com.conf
/usr/local/apps/apache2/bin/httpd -t
/usr/local/apps/apache2/bin/httpd -k graceful
```

Document root wajib mengarah ke folder `public`.

### 2.5 Queue worker dan scheduler

```bash
install -m 0644 deploy/webuzo/suntrack-queue.service /etc/systemd/system/suntrack-queue.service
install -m 0644 deploy/webuzo/suntrack-scheduler.service /etc/systemd/system/suntrack-scheduler.service
install -m 0644 deploy/webuzo/suntrack-scheduler.timer /etc/systemd/system/suntrack-scheduler.timer

systemctl daemon-reload
systemctl enable --now suntrack-queue.service
systemctl enable --now suntrack-scheduler.timer
```

## 3. Deployment pembaruan

Pastikan CI commit tujuan sudah lulus sebelum melakukan deployment.

### 3.1 Menjalankan deployment

```bash
cd /home/sunrise/suntrack-app
bash deploy/webuzo/deploy.sh
```

Hasil akhir yang berhasil adalah `DEPLOY_SUCCESS`. Jika commit server sudah sama dengan `origin/main`, script berhenti dengan `ALREADY_CURRENT`.

Script melakukan langkah berikut secara berurutan:

1. Memastikan repository bersih, environment produksi benar, PostgreSQL tersambung, serta ekstensi PHP tersedia.
2. Mengambil referensi terbaru `origin/main` dan berhenti bila tidak ada update.
3. Mencadangkan `.env` dan PostgreSQL ke `/root/suntrack-deploy-backups`.
4. Mengaktifkan maintenance mode dan menghentikan queue worker.
5. Melakukan fast-forward source, Composer install, dan Vite build.
6. Menjalankan seluruh migration yang masih pending.
7. Menjalankan `ProductionSeeder` yang aman diulang.
8. Memperbarui service, mengaktifkan aplikasi, lalu memeriksa migration dan endpoint health.

`DatabaseSeeder` tidak dijalankan di produksi karena berisi data awal dan akun contoh. Seeder baru yang aman untuk produksi harus idempotent dan didaftarkan pada `database/seeders/ProductionSeeder.php`.

`npm ci` digunakan agar dependency frontend mengikuti `package-lock.json`.

## 4. Verifikasi setelah deployment

```bash
/usr/local/apps/apache2/bin/httpd -t
systemctl --no-pager --full status suntrack-queue.service
systemctl --no-pager --full status suntrack-scheduler.timer
journalctl -u suntrack-queue.service -n 50 --no-pager
journalctl -u suntrack-scheduler.service -n 50 --no-pager

cd /home/sunrise/suntrack-app
sudo -u sunrise /usr/local/apps/php84/bin/php artisan migrate:status
sudo -u sunrise /usr/local/apps/php84/bin/php artisan schedule:list
sudo -u sunrise /usr/local/apps/php84/bin/php artisan queue:failed

redis-cli -h 127.0.0.1 -p 6379 ping
curl --resolve suntrack.sunriseadsacademy.com:443:127.0.0.1 -fsS https://suntrack.sunriseadsacademy.com/up
curl --resolve suntrack.sunriseadsacademy.com:443:127.0.0.1 -fsS https://suntrack.sunriseadsacademy.com/api/v1/health
```

Verifikasi melalui browser:

1. Login dengan username dan email.
2. Hak akses Super Admin, Admin, dan Tim.
3. CRUD Company, Brand, Campaign, Promotion, Task, dan Product.
4. Upload visual dan akses URL `/storage/...`.
5. Export report dan pemrosesan queue.
6. Activity Log dan halaman monitoring.

## 5. Rollback kode

Catat SHA commit yang aktif sebelum deployment:

```bash
cd /home/sunrise/suntrack-app
sudo -u sunrise git rev-parse HEAD
```

Jika kode baru gagal dan migrasi tidak mengubah data secara tidak kompatibel:

```bash
sudo -u sunrise /usr/local/apps/php84/bin/php artisan down
sudo -u sunrise git switch --detach COMMIT_SEBELUMNYA
sudo -u sunrise /usr/local/apps/php84/bin/php /usr/local/bin/composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
sudo -u sunrise npm ci
sudo -u sunrise npm run build
sudo -u sunrise /usr/local/apps/php84/bin/php artisan optimize
sudo -u sunrise /usr/local/apps/php84/bin/php artisan queue:restart
systemctl restart suntrack-queue.service
sudo -u sunrise /usr/local/apps/php84/bin/php artisan up
```

Jangan menjalankan `migrate:rollback` otomatis di produksi. Jika schema atau data sudah berubah secara tidak kompatibel, pulihkan dump PostgreSQL setelah menghentikan aplikasi dan memastikan target database benar.

Untuk kembali mengikuti branch utama:

```bash
sudo -u sunrise git switch main
sudo -u sunrise git pull --ff-only origin main
```

## 6. Kepemilikan dan secret

- `.env` dimiliki `sunrise:sunrise` dengan permission `600` agar PHP-FPM dan service dapat membacanya.
- `storage` dan `bootstrap/cache` harus dapat ditulis oleh user `sunrise`.
- Document root tidak boleh menunjuk ke root repository.
- Password dan secret tidak disimpan di Git.
- Redis memakai database `2` untuk queue/session dan database `3` untuk cache agar terpisah dari LMS.
