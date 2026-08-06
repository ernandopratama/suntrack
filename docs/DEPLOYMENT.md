# SunTrack — Generic Containerized Deployment Runbook

This runbook provides step-by-step instructions for deploying SunTrack to any production environment supporting Docker and Docker Compose (e.g., Ubuntu Server, Debian VPS, AWS EC2, DigitalOcean Droplet, or Portainer). This deployment strategy is completely vendor-agnostic and relies exclusively on standardized container orchestration.

---

## 1. Production Server Prerequisites
- A Linux server (e.g., Ubuntu 22.04 LTS or 24.04 LTS) with at least 2GB RAM and 20GB SSD storage.
- Docker Engine and Docker Compose V2 installed:
  ```bash
  curl -fsSL https://get.docker.com -o get-docker.sh
  sudo sh get-docker.sh
  sudo usermod -aG docker $USER
  ```
- Domain name (e.g., `suntrack.yourdomain.com`) pointed to your server's public IP address.

---

## 2. Server Deployment Steps

### Step 1: Transfer / Clone Project to Server
```bash
git clone https://github.com/suntrack/suntrack.git /var/www/suntrack
cd /var/www/suntrack
```

### Step 2: Configure Production Environment (`.env`)
Copy the template and configure production secrets:
```bash
cp .env.example .env
nano .env
```
Ensure the following critical production variables are set:
```ini
APP_NAME=SunTrack
APP_ENV=production
APP_KEY= # Will be generated in Step 4
APP_DEBUG=false
APP_URL=https://suntrack.yourdomain.com

# Docker Service Bindings
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=suntrack_prod
DB_USERNAME=suntrack_admin
DB_PASSWORD=YourStrongSecurePasswordHere!

# Redis Infrastructure
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Storage Service Abstraction
FILESYSTEM_DISK=local # Or s3 / google_drive
```

### Step 3: Build & Launch Container Stack
Build the production Docker images and start the services:
```bash
docker compose up -d --build
```
Verify all 6 containers (`suntrack-app`, `suntrack-nginx`, `suntrack-mysql`, `suntrack-redis`, `suntrack-queue-worker`, `suntrack-scheduler`) are running:
```bash
docker compose ps
```

### Step 4: Initialize Production Database & Assets
Execute initialization commands inside the running `app` container:
```bash
# Generate production encryption key
docker compose exec app php artisan key:generate --force

# Optimize Composer autoloader for production
docker compose exec app composer install --no-dev --optimize-autoloader

# Run database migrations and seed default RBAC roles
docker compose exec app php artisan migrate --force --seed

# Build production Vue 3 SPA bundle
docker compose exec app npm ci
docker compose exec app npm run build

# Cache configuration, routes, and views for maximum performance
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

---

## 3. Portainer Management (Optional)
If managing deployment via [Portainer](https://www.portainer.io/):
1. Navigate to **Stacks** -> **Add stack**.
2. Name the stack `suntrack-prod`.
3. Select **Repository** or paste the contents of `docker-compose.yml`.
4. Define environment variables in the Portainer UI corresponding to your `.env` production secrets.
5. Click **Deploy the stack**.

---

## 4. Health Verification & Maintenance
- **Health Check API:** Verify system operational status by querying:
  ```bash
  curl -i http://localhost:8000/api/v1/health
  ```
  Expected output: `200 OK` with JSON payload verifying Database, Cache, Queue, and Storage status.
- **Automated Backups:** The containerized scheduler automatically executes `suntrack:backup-db` daily at 01:00 AM, archiving database dumps to your configured `StorageService` driver.
- **Viewing Worker Logs:**
  ```bash
  docker compose logs --tail=100 -f queue-worker
  ```
