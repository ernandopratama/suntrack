# SunTrack - Docker Image Reference

Docker bukan jalur deployment produksi VPS Webuzo saat ini. Deployment produksi mengikuti [DEPLOYMENT.md](DEPLOYMENT.md).

Repository hanya menyediakan `Dockerfile` untuk membuktikan image produksi dapat dibangun oleh CI. File Docker Compose tidak tersedia, sehingga perintah `docker compose up` tidak menjadi bagian dari runbook aktif.

## Image stages

### Frontend

- Base image: Node.js 24 Alpine.
- Dependency: `npm ci`.
- Output: asset Vite pada `public/build`.

### Runtime

- Base image: PHP 8.4 FPM Bookworm.
- Database extensions: `pdo_mysql` dan `pdo_pgsql`.
- Runtime extensions: `bcmath`, `gd`, `intl`, `mbstring`, `opcache`, `pcntl`, `redis`, dan `zip`.
- Composer dependency dipasang tanpa development packages.

## CI verification

Stage Docker menjalankan:

```bash
docker buildx build --target production --load .
```

Image ini belum dipublikasikan ke registry dan tidak menjalankan Nginx, queue worker, scheduler, database, atau Redis. Komponen tersebut dikelola langsung oleh Webuzo, PostgreSQL, Redis, dan systemd pada produksi.
