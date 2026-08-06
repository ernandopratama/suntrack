# SunTrack — Local Development Guide (Docker-First)

SunTrack adopts a **Docker-First** development workflow. Developers do not need to install PHP, Composer, Node.js, MySQL, or Redis directly on their local host OS. The entire development stack is containerized and orchestrated via Docker Compose.

---

## 1. Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running (Windows, macOS, or Linux).
- Git installed.

---

## 2. Initial Setup

### Clone Repository & Environment Setup
```bash
git clone https://github.com/suntrack/suntrack.git
cd suntrack
cp .env.example .env
```

### Start the Docker Stack
Launch all 6 core services (`app`, `nginx`, `mysql`, `redis`, `queue-worker`, `scheduler`) in detached mode:
```bash
docker compose up -d
```

### Install Backend & Frontend Dependencies inside Container
Execute Composer and NPM commands directly inside the `app` container:
```bash
# Install PHP dependencies
docker compose exec app composer install

# Generate Laravel Application Key
docker compose exec app php artisan key:generate

# Run Database Migrations & Seeders (RBAC & Roles)
docker compose exec app php artisan migrate --seed

# Install Node.js dependencies & compile frontend assets
docker compose exec app npm install
docker compose exec app npm run build
```

---

## 3. Daily Development Workflow

### Running Vite Development Server (Hot Module Replacement)
To develop Vue 3 components with instantaneous hot-reloading:
```bash
docker compose exec app npm run dev -- --host 0.0.0.0
```
Access the application at: **http://localhost:8000** (Vite HMR runs on port **5173**).

### Running Artisan Commands
All Laravel CLI commands must be executed through the `app` container:
```bash
# Clear all caches
docker compose exec app php artisan optimize:clear

# Run automated tests (PHPUnit / Pest)
docker compose exec app php artisan test

# Check route registrations
docker compose exec app php artisan route:list
```

### Inspecting Background Jobs & Schedules
The `queue-worker` and `scheduler` services run automatically in the background. To inspect their live logs:
```bash
# View live queue execution logs
docker compose logs -f queue-worker

# View live scheduler execution logs
docker compose logs -f scheduler
```

---

## 4. Troubleshooting
- **Port Conflict:** If port `8000`, `3306`, or `6379` is already in use on your host, modify `APP_PORT`, `FORWARD_DB_PORT`, or `FORWARD_REDIS_PORT` in your `.env` file and restart:
  ```bash
  docker compose down && docker compose up -d
  ```
- **Permission Reset:** If you encounter storage write errors:
  ```bash
  docker compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
  ```
