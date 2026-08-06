# Architecture Decision Records (ADR)

This document serves as the single source of truth for all important architectural and technical decisions made throughout the SunTrack project.

---

### ADR-001
### Title: Laravel 12 as the backend framework
**Date:** 2026-07-26
**Status:** Accepted
**Context:** Need a robust backend capable of scaling, enforcing enterprise logic, and providing built-in security features.
**Decision:** Use Laravel 12 (PHP 8.2+).
**Rationale:** Laravel provides extensive out-of-the-box features like Eloquent ORM, Sanctum for auth, and a structured service container, which aligns perfectly with the 5+ year maintenance horizon.
**Consequences:** Backend acts strictly as an API, completely decoupled from the UI rendering layer.
**Related Components:** Backend, Database

---

### ADR-002
### Title: Vue 3 + Tailwind CSS as the frontend stack
**Date:** 2026-07-26
**Status:** Accepted
**Context:** Need a modern, reactive, and maintainable frontend application replacing complex spreadsheets.
**Decision:** Use Vue 3 (Composition API) with Pinia for state management and Tailwind CSS for styling.
**Rationale:** Vue 3 Composition API allows for building highly reusable logic via composables. Tailwind provides utility-first CSS enabling rapid UI development without bloated stylesheets.
**Consequences:** Requires an SPA architecture. SEO is not a priority for an internal tool, so standard Vue SPA is optimal.
**Related Components:** Frontend

---

### ADR-003
### Title: Laravel Sanctum for authentication
**Date:** 2026-07-26
**Status:** Accepted
**Context:** Securing the API for internal Admins.
**Decision:** Implement Laravel Sanctum SPA Authentication.
**Rationale:** It handles CSRF and cookie-based sessions securely without the overhead of OAuth/JWT for internal single-domain usage.
**Related Components:** Security, API

---

### ADR-004
### Title: REST API with `/api/v1` versioning
**Date:** 2026-07-26
**Status:** Accepted
**Context:** To prevent future breaking changes as mobile apps or external integrations scale.
**Decision:** Hardcode `/api/v1/auth`, `/api/v1/admin`, and `/api/v1/public` routing structures.
**Rationale:** Versioning from day 1 avoids migration pain. Separating `admin` (authenticated) from `public` (tokenized) ensures clear authorization boundaries.
**Related Components:** API

---

### ADR-005
### Title: Standardized API response format
**Date:** 2026-07-26
**Status:** Accepted
**Context:** Frontend clients need a predictable response format for both success and error states to build generic handlers.
**Decision:** Use a unified structure: `{ success: bool, message: string, data/errors: object }`. Implemented via `ApiResponse` trait.
**Related Components:** API, Frontend

---

### ADR-006
### Title: Brand authentication using Secure Public Links
**Date:** 2026-07-26
**Status:** Accepted
**Context:** External brands need to review and approve pricing, but forcing them to create user accounts causes onboarding friction.
**Decision:** Generate cryptographically secure URL tokens (`Str::random(64)`) mapped to specific Campaigns/Promotions, bypassing standard login.
**Rationale:** High usability for external stakeholders while maintaining auditability by capturing their identity (Name/Position) on first action.
**Related Components:** Security, Frontend

---

### ADR-007
### Title: Generic Activity Logger service
**Date:** 2026-07-26
**Status:** Accepted
**Context:** Need an immutable audit trail for all business-critical actions.
**Decision:** Build a global `ActivityLogger` service using a polymorphic `activity_logs` table.
**Rationale:** Decouples logging logic from business models. Any entity (Campaign, Auth, Promotion) can write logs using the same interface.
**Related Components:** Backend, Database

---

### ADR-008
### Title: PHP Enums for business statuses
**Date:** 2026-07-26
**Status:** Accepted
**Context:** Magic strings (`'Pending'`, `'Approved'`) cause typos and scattered logic.
**Decision:** Centralize all statuses using PHP 8.1 Enums (e.g., `CampaignStatus`, `ApprovalStatus`).
**Rationale:** Provides type-safety, centralizes available statuses, and simplifies frontend sharing.
**Related Components:** Backend

---

### ADR-009
### Title: Reusable Vue component architecture
**Date:** 2026-07-26
**Status:** Accepted
**Context:** Avoid duplicating UI code (tables, modals, buttons) across different modules.
**Decision:** Build a strict, presentation-only UI component library in `resources/js/components`.
**Rationale:** Drastically speeds up future development and ensures visual consistency.
**Related Components:** Frontend

---

### ADR-010
### Title: Soft Deletes for critical business entities
**Date:** 2026-07-26
**Status:** Accepted
**Context:** Admins occasionally make destructive mistakes. Data loss is unacceptable.
**Decision:** Implement Eloquent `SoftDeletes` globally on Campaigns, Promotions, Tasks, and Products. Foreign keys must `restrictOnDelete`.
**Rationale:** Ensures history and relational integrity are preserved even if an admin triggers a delete.
**Related Components:** Database

---

### ADR-011
### Title: Clean Architecture with thin Controllers
**Date:** 2026-07-26
**Status:** Accepted
**Context:** Complex business logic in controllers leads to unmaintainable spaghetti code.
**Decision:** Use Form Requests for validation, Services for business logic, and API Resources for data transformation. Controllers only coordinate.
**Related Components:** Backend

---

### ADR-012
### Title: Future-ready architecture supporting multi-tenancy
**Date:** 2026-07-26
**Status:** Accepted
**Context:** MVP is single-company, but the platform must support multiple Companies and Brands in the future without DB schema rewrites.
**Decision:** Add `company_id` and `brand_id` foreign keys to core entities from Day 1. Auth logic will rely on these relationships, even if initially there's only one row in the database.
**Related Components:** Database, Architecture

---

### ADR-013
### Title: Human-Readable Promotion Codes and Lifecycle Management
**Date:** 2026-07-26
**Status:** Accepted
**Context:** While UUIDs provide secure primary keys, users and external brand stakeholders need human-readable identifiers for searching, reports, and integrations. Additionally, promotion status changes must follow strict business rules.
**Decision:** 
1. Generate unique human-readable codes formatted as `PRM-YYYYMM-XXXX` (e.g., `PRM-202607-0001`) automatically upon creation.
2. Centralize promotion status lifecycle using `PromotionStatus` Enum (`Pending`, `Partially Approved`, `Approved`, `Rejected`, `Active`, `Completed`, `Cancelled`) with strict transition validation.
**Rationale:** Human-readable codes make communication and reporting error-free. Centralized state transition rules prevent illegal lifecycle jumps (e.g., bypassing brand approval).
**Related Components:** Backend, Database, Frontend

---

### ADR-014
### Title: Promotion Pricing Snapshots & Floor Price Enforcement
**Date:** 2026-07-27
**Status:** Accepted
**Context:** Promotions often run over extended periods or historical audits require verifying what the retail price was when a promotion was agreed upon. If a master product variant's price changes in the catalog, historical promotions must not be corrupted or altered. Furthermore, sales teams must never sell variants below their minimum profitable floor price.
**Decision:**
1. Use the `promotion_variant` pivot table as the immutable historical record for pricing during a promotion by storing `normal_price_snapshot`, `campaign_price`, `discount_price`, `bottom_price`, and `promotion_stock`.
2. Enforce strict margin protection floor rules in backend Form Requests (`PromotionVariantRequest`) and frontend validators: `campaign_price` and `discount_price` must be greater than or equal to `bottom_price`, and `campaign_price` must be less than or equal to `normal_price_snapshot`.
**Rationale:** Preserves historical financial integrity across all promotional campaigns while guaranteeing margin protection at both the UI and database validation layers.
**Related Components:** Backend, Database, Frontend

### ADR-015
### Title: Secure Brand Collaboration & Per-Variant Approval Workflow
**Date:** 2026-07-27
**Status:** Accepted
**Context:** SunTrack operates as a collaborative enterprise platform where internal Admins propose promotional campaigns and pricing to external Brands. External Brand reviewers require a frictionless review mechanism without mandatory account creation or login, while SunTrack requires strict security, immutable audit trails, and granular per-item approval.
**Decision:**
1. **Secure Public Link (64-character Random Token):** Implement polymorphic `secure_links` attached to Promotions/Campaigns. Enforce strict audit attributes (`last_accessed_at`, `view_count`, `created_by`, `status`) and allow Admin revocation and expiration date controls.
2. **Reviewer Identification & Storage:** Require external reviewers to submit identity data (Name, Position, Company Name, WhatsApp Number) before performing actions. Cache this identity in browser `localStorage` and log it alongside client IP and User Agent in all audit entries.
3. **Per-Variant Approval & Mandatory Rejection Notes:** Allow Brand reviewers to approve or reject individual variant mappings rather than an all-or-nothing promotion approval. Rejections require a mandatory explanatory note.
4. **Immutable Approval History & Dynamic Status Calculation:** Record all status transitions in an immutable `approval_histories` table. Automatically derive and update the parent Promotion status (`Pending`, `Partially Approved`, `Approved`, `Rejected`) based on variant states.
5. **Polymorphic Discussion & Unified Activity Timeline:** Provide a polymorphic `comments` table for 2-way Admin-Brand discussion, and combine all actions into a unified chronological timeline via `ActivityLogger`.
**Rationale:** Balances frictionless external brand user experience with enterprise-grade security, auditability, and granular negotiation capabilities.
**Related Components:** Backend, Database, Security, Frontend

### ADR-016
### Title: Dashboard Aggregation Architecture & Reusable Service Foundations (Reporting & Notification)
**Date:** 2026-07-27
**Status:** Accepted
**Context:** As SunTrack scales, the Admin Dashboard must function as an Operational Command Center rather than a static statistical overview. Performing heavy data calculations on the client-side or executing unoptimized database queries risks severe N+1 bottlenecks and UI latency. Furthermore, enterprise export capabilities and notification workflows require strict separation of concerns to prevent code duplication and vendor lock-in.
**Decision:**
1. **Backend SQL Aggregation & Cache-Ready API:** Compute all 12 operational KPI metrics and deadline monitoring categorizations (Today, Tomorrow, 7 Days, Overdue, Expiring Links) directly in SQL using aggregate queries within `DashboardController`. Structure the JSON payload (`DashboardResource`) concisely to allow future wrapping in Laravel Cache without altering API contracts.
2. **Interactive Command Center Workspace:** Design `Dashboard.vue` with clickable KPI Cards that direct users to filtered lists (e.g., Pending Approvals -> `/promotions`), visual status indicators for deadline items (`green`=Safe, `yellow`=Approaching, `red`=Overdue), and an extensible grid supporting future KPI additions (Approval Rate, Average Approval Time, Total Comments, Activity Today).
3. **Reporting Service Foundation (Adapter Pattern):** Establish `ReportExporterInterface` and `ReportGeneratorInterface`. Implement `ReportingService` to manage export adapters for 5 core domains (Campaign, Promotion, Approval, Product, Activity) with an initial native UTF-8 `CsvExporter` driver, enabling future addition of Excel/PDF/Google Sheets drivers without modifying business logic.
4. **Notification Service Foundation (Driver Manager with Log Mode):** Establish `NotificationDriverInterface` and `NotificationService` managing `whatsapp`, `email`, `in_app`, and `webhook` drivers. Operate all drivers in **Log/Audit Mode** during Sprint 7, enforcing strict metadata requirements (`channel`, `recipient`, `subject`, `status`, `sent_at`, `related_entity`, `related_entity_id`) recorded in system logs and `ActivityLogger` to prepare for live API gateway integration in Sprint 8.
**Rationale:** Guarantees enterprise-grade performance, maintainability, extensibility, and seamless transition from foundational audit modes to live external integrations.
**Related Components:** Backend, Architecture, Frontend, Reporting, Notification

### ADR-017
### Title: Automation Foundation, Graceful Gateway Fallback, and SpreadsheetML / PDF Stream Exporters
**Date:** 2026-07-27
**Status:** Accepted
**Context:** In Sprint 8, SunTrack transitioned from internal CRUD and foundational reporting/notification contracts to active automation and external integration. High-latency processes such as HTTP-based WhatsApp messaging, SMTP email sending, large dataset exporting, catalog synchronization, and bulk database updates must never block synchronous web request cycles. Furthermore, exporting spreadsheets and print reports to enterprise formats (Excel and PDF) must be reliable across varying hosting environments without requiring external binary dependencies or C-libraries (such as `libgd`, `wkhtmltopdf`, or memory-heavy PHP Excel bundles).
**Decision:**
1. **Queue & Scheduler Architecture:** All heavy external operations (notifications, report generation, catalog syncs, and system maintenance) must be wrapped in dedicated asynchronous Laravel Jobs (`SendNotificationJob`, `GenerateReportExportJob`, `SyncCatalogDataJob`, etc.) dispatched to background queues. Recurring tasks (daily approval/deadline reminders, hourly expired link monitoring, temporary file cleanup) are registered in `routes/console.php` using Laravel's native scheduler console commands.
2. **Graceful Fallback to Log Mode:** Notification drivers (`WhatsAppDriver` and `EmailDriver`) must dynamically inspect runtime configuration (`.env` credentials). If live API tokens or SMTP hosts are unconfigured or unreachable, the driver must gracefully fallback to **Log Mode**—recording the payload and delivery intent to system logs and `ActivityLogger` without throwing fatal runtime exceptions.
3. **Transactional Batch Approval Workflow:** Batch operations (`approve_selected`, `reject_selected`, `approve_all`, `reject_all`) in Admin and Brand Public Review controllers must execute within atomic database transactions (`DB::transaction`). Each variant transition generates an immutable `approval_histories` record, triggers automatic Promotion parent status recalculation, and records a consolidated audit event in `ActivityLogger`.
4. **Native Stream Exporters (SpreadsheetML & HTML/PDF):** Implement `ExcelExporter` using XML Spreadsheet 2003 (SpreadsheetML) standard and `PdfExporter` using clean print-ready HTML stream rendering. These adapters integrate seamlessly into `ReportingService` via constructor dependency injection and allow multi-sheet, styled spreadsheet generation and printable reports with zero external library dependencies.
**Rationale:** Ensures high resilience, zero zero-day dependency vulnerabilities, optimal background performance, and seamless enterprise deployment across standard hosting environments.
**Related Components:** Backend, Queue, Scheduler, Notification, Reporting, Frontend

### ADR-018
### Title: Docker-First Architecture for Local Development and Production Parity
**Date:** 2026-07-27
**Status:** Accepted
**Context:** Consistent environments across local development, CI/CD testing, and staging/production deployments are critical to eliminate "it works on my machine" defects. Relying on host-installed PHP, Node.js, Composer, or web servers creates environment drift and onboarding friction.
**Decision:**
1. **Containerized Development & Production:** Adopt Docker Compose as the standard local development environment and Docker containers as the production runtime. Define 6 core services: `app` (Laravel PHP 8.2-FPM + Node.js 20), `nginx` (Alpine web server), `mysql` (MySQL 8.0), `redis` (Redis 7 Alpine), `queue-worker` (background job processing), and `scheduler` (automated cron execution).
2. **Zero Host Dependencies:** All application commands (`composer install`, `php artisan migrate`, `npm run build`, `php artisan test`) must execute cleanly inside the container via `docker compose exec app ...` without requiring host PHP/Node installations.
3. **Multi-Stage Production Build Parity:** Utilize a multi-stage Dockerfile (`dependencies`, `frontend-build`, `production`) to compile frontend assets and install vendor dependencies cleanly, ensuring exact environment parity from development to production.
**Rationale:** Guarantees reproducible builds, frictionless onboarding, zero host environment drift, and seamless container orchestration across Ubuntu, VPS, and Portainer deployments.
**Related Components:** Architecture, Docker, DevOps, Infrastructure

### ADR-019
### Title: Redis as Standard Enterprise Cache, Queue, and Session Backend
**Date:** 2026-07-27
**Status:** Accepted
**Context:** High-throughput enterprise operations require low-latency data access and reliable background job execution. Database-backed cache or queue drivers create unnecessary I/O load and contention on primary relational tables during peak promotional campaign spikes.
**Decision:**
1. **Redis Standardization:** Establish Redis 7 as the default infrastructure service for `CACHE_STORE`, `QUEUE_CONNECTION`, and `SESSION_DRIVER` across all environments.
2. **Graceful Driver Degradation:** In local or test environments where Redis may be unreachable or during initial setup, fallback cleanly to database or array drivers while logging warnings, ensuring development continuity.
**Rationale:** Unlocks microsecond-latency caching for system settings and analytics dashboards, eliminates database lock contention from queue workers, and provides scalable session handling.
**Related Components:** Architecture, Redis, Cache, Queue, DevOps

### ADR-020
### Title: Production Readiness Hardening, Dynamic System Settings, and Media Storage Abstraction
**Date:** 2026-07-27
**Status:** Accepted
**Context:** Transitioning SunTrack to production requires dynamic configuration management without requiring `.env` file modifications or server restarts, secure multi-cloud media storage for product attachments and backups, comprehensive RBAC authorization, security header enforcement, and audit-ready login monitoring.
**Decision:**
1. **Dynamic System Settings Abstraction:** Implement `SettingsService` backed by a `system_settings` table and forever-caching in Redis (`settings_cache`). Provide both public branding endpoints and protected admin management interfaces.
2. **Multi-Cloud Storage Abstraction:** Implement `StorageService` implementing `StorageDriverInterface` with `LocalDriver`, `S3Driver`, and `GoogleDriveDriver` support resolved dynamically via system settings or `.env` configuration.
3. **Security Hardening & Login Auditing:** Enforce global security headers (`X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `CSP`) via `SecurityHeadersMiddleware`, audit all login attempts in `login_histories`, and implement an automated database snapshot console command (`suntrack:backup-db`) scheduled daily at 01:00 AM.
4. **RBAC Authorization:** Integrate `spatie/laravel-permission` with 4 core enterprise roles (`Super Admin`, `Brand Manager`, `Finance Auditor`, `Operational Staff`) and granular permission seeding.
**Rationale:** Provides enterprise security compliance, operational agility, multi-cloud storage flexibility, and robust disaster recovery capabilities.
**Related Components:** Architecture, Security, Storage, Settings, RBAC, Console

### ADR-021
### Title: Observability Architecture, CI/CD Foundation, and Disaster Recovery Governance
**Date:** 2026-07-27
**Status:** Accepted
**Context:** Enterprise reliability requires real-time observability into database queries, job queues, and system performance without causing production overhead. Additionally, automated CI/CD validation is necessary to enforce quality gates (testing, formatting, static analysis, asset bundling) prior to deployment, and standardized Disaster Recovery Procedures (DRP) must be established to ensure rapid restoration of databases and multi-cloud media storage during outages.
**Decision:**
1. **Tiered Observability Strategy:** Prepare architectural foundations for Laravel Telescope (development debugging only, disabled in production), Laravel Pulse (production performance monitoring secured via RBAC), slow query execution plan logging (queries > 100ms logged to `ActivityLogger` and system logs), daily log rotation (`max_files: 30`), and active Redis/Queue health monitoring.
2. **Automated CI/CD Quality Gates:** Implement GitHub Actions workflow (`.github/workflows/ci.yml`) validating Docker container builds, PHPUnit tests, Laravel Pint formatting, Larastan static analysis, and Vite SPA compilation on all branch pushes and pull requests.
3. **Disaster Recovery Governance:** Formalize container-first restore runbooks (`docs/BACKUP_AND_RECOVERY.md`), retention schedules (30 days daily, 12 months monthly archives), and automated staging restore verification drills.
4. **100% Docker-First Documentation Parity:** Standardize all developer and deployment runbooks (`LOCAL_DEVELOPMENT.md`, `DOCKER.md`, `DEPLOYMENT.md`) to explicitly execute commands inside containers (`docker compose exec app ...`), eliminating host dependency drift.
**Rationale:** Ensures proactive incident detection, strict code quality assurance, rapid outage recovery, and zero host onboarding friction.
**Related Components:** DevOps, Architecture, Observability, CI/CD, Security, Documentation
### ADR-022
### Title: Database Performance Indexing & Read/Write Replica Architecture
**Date:** 2026-07-27
**Status:** Accepted
**Context:** As SunTrack scales to 100,000+ rows per core table (campaigns, promotions, products, variants, activity_logs), unindexed queries on status, foreign key, and date range columns introduce full-table scan risk and unpredictable latency growth.
**Decision:**
1. **Composite Performance Indexes:** Add production indexes targeting the highest-frequency `WHERE`, `JOIN`, and `ORDER BY` patterns identified through slow query log analysis. All indexes are deployed via a dedicated migration (`2026_07_27_130001_add_performance_indexes_to_tables.php`) and documented in `docs/DATABASE.md`.
2. **Read/Write Replica Separation:** Configure `config/database.php` MySQL connection with `read` / `write` host arrays and `'sticky' => true` to support horizontal read scaling without code changes. Single-host environments (local Docker) operate transparently without configuration changes.
3. **Index Naming Convention:** All indexes follow the `{table_abbr}_{column_abbr(s)}_idx` naming pattern to ensure consistent identification in query plans and documentation.
**Rationale:** Composite indexes reduce full-table scans to index-range scans, directly preventing P95 latency degradation at scale. Sticky read replicas eliminate replication-lag race conditions at the framework configuration level without application layer changes.
**Related Components:** Database, Performance, Migrations, Configuration

---

### ADR-023
### Title: Redis Tag-Based Caching Strategy & Automated Invalidation Observers
**Date:** 2026-07-27
**Status:** Accepted
**Context:** Dashboard KPI aggregations execute multiple `COUNT()` and `SUM()` queries across large tables on every page load. Without caching, response times increase linearly with data volume, violating the SLA target of ≤ 100ms for dashboard cold queries and ≤ 15ms for subsequent warm loads.
**Decision:**
1. **CacheService Abstraction Layer:** Create `App\Services\Cache\CacheService` encapsulating Redis tag-based `remember()`, `rememberForever()`, and `flushTags()` operations. The service includes a graceful fallback to non-tagged cache drivers (array, file) used in testing environments.
2. **Tag-Based Cache Namespacing:** Define canonical tag taxonomies (`dashboard`, `campaigns`, `promotions`, `products`, `variants`, `catalog`, `settings`) documented in `docs/CACHING.md`.
3. **Automated Invalidation via Eloquent Observers:** Register five model observers (`CampaignObserver`, `PromotionObserver`, `ProductObserver`, `VariantObserver`, `SystemSettingObserver`) in `AppServiceProvider`. Observers automatically flush relevant tag groups on every `saved()` and `deleted()` Eloquent event, ensuring cache freshness without manual developer intervention.
4. **TTL Policy:** Dashboard and operational data TTL set at 300 seconds. Catalog data at 600 seconds. System settings cached indefinitely (`rememberForever`) with immediate observer-driven invalidation.
**Rationale:** Tag-based caching allows surgical invalidation of data subsets without clearing the entire Redis keyspace. Automated observers eliminate the risk of developers forgetting to invalidate cache after data mutations — a common source of stale data bugs.
**Related Components:** Cache, Redis, Observers, Performance, AppServiceProvider

---

### ADR-024
### Title: Enterprise Performance Benchmarking Standard (SLA Compliance Protocol)
**Date:** 2026-07-27
**Status:** Accepted
**Context:** Without an automated, reproducible benchmarking protocol, performance regressions can go undetected across sprints, especially as new features add query complexity or new relationships are introduced.
**Decision:**
1. **Dedicated Benchmark Artisan Commands:** Implement `suntrack:benchmark-seed` (high-throughput chunked bulk seeder for realistic 100k-row datasets) and `suntrack:benchmark-run` (automated latency measurement with SLA pass/warn/fail verdict rendering and JSON export).
2. **Mandatory Pre-Sprint-Close Regression Protocol:** Every sprint must execute the full benchmark protocol against a fresh 100k-row dataset before being declared complete. Benchmark results must be archived in `docs/benchmark-history/` with sprint attribution.
3. **SLA Thresholds:** Dashboard Cold Cache ≤ 100ms, Dashboard Warm Cache ≤ 15ms, Catalog/Promotion/Campaign Listings ≤ 50ms. Exceeding the escalation threshold by >20% over baseline requires root cause analysis before merge.
4. **Benchmark Documentation:** Full benchmarking procedures, SLA targets, dataset specifications, and regression protocols are documented in `docs/PERFORMANCE.md`.
**Rationale:** A codified, automated benchmark protocol transforms performance from a subjective concern into an objective, measurable engineering standard. Sprint 10 results validate the architecture: average system latency of 12.99ms, 92% Redis cache latency reduction.
**Related Components:** Performance, Console Commands, CI/CD, Documentation

---

### ADR-025
### Title: Enterprise Observability & Metrics Monitoring Foundation
**Date:** 2026-07-27
**Status:** Accepted
**Context:** SunTrack's existing slow query logger provides reactive observability (alerts after slow queries occur). For proactive enterprise monitoring, we need time-series metrics on API latency, cache efficiency, queue health, scheduler execution history, and memory utilization — structured for future export to Prometheus, Grafana, or OpenTelemetry.
**Decision:**
1. **MetricsService Abstraction Layer:** Create `App\Services\Monitoring\MetricsService` providing a clean API for recording request latency, cache hit/miss events, queue job metrics, scheduler execution history, and memory usage statistics.
2. **Prometheus Text Exposition Format:** Implement `exportPrometheusMetrics()` method in `MetricsService` generating standard Prometheus exposition text format, enabling future integration with Prometheus scrapers, Grafana dashboards, or OpenTelemetry collectors without code changes.
3. **In-Process Redis Metric Storage:** Metrics are stored in Redis (or array cache in testing) with appropriate TTLs. This avoids the overhead of a dedicated metrics database while remaining compatible with external exporters.
4. **Cache Hit Ratio SLA:** Production systems must maintain a Redis cache hit ratio ≥ 85% during normal business hours. Deviations trigger operational review.
**Rationale:** Observability must be designed as a first-class architectural concern rather than added as an afterthought. Building an abstraction layer now ensures that switching to Prometheus/OpenTelemetry in the future requires zero business logic changes.
**Related Components:** Monitoring, Redis, Performance, DevOps, MetricsService

---

### ADR-026
### Title: Multi-Stage CI/CD Pipeline Architecture for Cross-Platform Portability
**Date:** 2026-07-27
**Status:** Accepted
**Context:** The original monolithic CI pipeline executed all quality gates in a single job, preventing parallel execution and making migration to alternative CI platforms (GitLab CI, Azure DevOps, Jenkins) needlessly complex due to platform-specific syntax coupling.
**Decision:** Decompose the CI pipeline into 7 independent, dependency-aware stages with clear naming conventions, enabling both sequential and parallel execution depending on the target CI platform:
1. **Stage 1 — Static Analysis (PHPStan):** Code-quality gate. Fails fast on type errors and architectural violations.
2. **Stage 2 — Coding Standards (Laravel Pint):** Enforce PSR-12 / Laravel formatting conventions.
3. **Stage 3 — Unit Tests:** Isolated fast tests with SQLite in-memory. No external services required.
4. **Stage 4 — Feature Tests:** Integration tests with MySQL 8.0 + Redis 7 service containers.
5. **Stage 5 — Build Docker Image:** Verify Dockerfile produces a valid application container.
6. **Stage 6 — Build Frontend Assets (Vite):** Verify TypeScript/JavaScript assets compile without errors; upload build artifact.
7. **Stage 7 — Security Scan (composer audit):** Check Composer dependency tree for known CVEs.
**Rationale:** Stage separation allows parallel execution (Stages 1+2+6+7 run concurrently), reduces total CI time, provides clear failure attribution per discipline, and enables platform migration without structural changes.
**Related Components:** CI/CD, DevOps, GitHub Actions, GitLab CI, Security

---

### ADR-027
### Title: Sprint 10 Architecture Approval — Performance & Scalability Foundation
**Date:** 2026-07-27
**Status:** Accepted
**Context:** Sprint 10 completed the first enterprise-grade performance optimization sprint for SunTrack, targeting N+1 elimination, sub-15ms caching, composite database indexing, observability, and benchmark SLA validation.
**Decision:** Accept the following architectural decisions as production standards:
- Repository Pattern (via `RepositoryInterface` + `BaseRepository`) as the **mandatory data access pattern** replacing direct Eloquent model usage in Controllers.
- Redis Tag-Based Cache (`CacheService`) as the **mandatory caching abstraction**. No direct `Cache::` facade calls permitted in business logic.
- Model Observers as the **mandatory cache invalidation mechanism**. Manual `Cache::forget()` calls in Controllers/Services are prohibited.
- `suntrack:benchmark-run` execution as a **mandatory pre-sprint-close gate**. A sprint cannot be approved if any SLA threshold is exceeded.
- `docs/DATABASE.md`, `docs/CACHING.md`, `docs/PERFORMANCE.md` as **living documentation** updated on every sprint.
**Benchmark Results at Approval:**
- Average System Latency: **12.99 ms** across all test cases.
- Cache Warm Latency: **1.91 ms** (92% reduction vs cold SQL aggregation at 23.07 ms).
- Feature Test Suite: **7/7 passing, 31 assertions**.
**Rationale:** Formalizing these patterns as architectural standards prevents individual developers from bypassing performance patterns under deadline pressure, ensuring long-term scalability without architectural debt.
**Related Components:** Architecture, Repository Pattern, Cache, Performance, Database, CI/CD

---

### ADR-028
### Title: Global Search Engine Architecture — Driver Pattern (Sprint 11)
**Date:** 2026-07-27
**Status:** Accepted
**Context:** SunTrack Admin users need to quickly locate Campaigns, Promotions, Products, Variants, Activity Logs, and Comments from a single unified search interface. The initial implementation must use MySQL LIKE queries, but the architecture must allow migration to Meilisearch, Elasticsearch, or OpenSearch without any changes to Controllers or the Frontend API contract.
**Decision:**
1. **SearchDriverInterface Contract:** Define `App\Contracts\Search\SearchDriverInterface` with `search()`, `isAvailable()`, and `driverName()` methods.
2. **Three Driver Implementations:** Implement `DatabaseSearchDriver` (production default), `MeilisearchSearchDriver` (stub, activated via `MEILISEARCH_HOST`), and `ElasticsearchSearchDriver` (stub, activated via `ELASTICSEARCH_HOST`).
3. **Driver Resolution Chain:** `GlobalSearchService` resolves the active driver at boot: Meilisearch → Elasticsearch → Database (fallback). Switching engines requires only setting the appropriate environment variable — zero code changes.
4. **Cache Layer:** All search results cached for 60 seconds per query+types+companyId hash to minimize repeated identical queries during user typing.
5. **Multi-Entity Search:** The Database driver searches across 6 entity types with exact-match relevance prioritization (exact matches return before partial LIKE matches).
**Rationale:** Driver pattern ensures the search implementation remains swappable without impacting the API contract. MySQL is fast enough for initial scale; Meilisearch/Elasticsearch activation is configuration-only.
**Related Components:** Search, Architecture, GlobalSearchService, SearchDriverInterface

---

### ADR-029
### Title: Notification Delivery Tracking — 6-Status Lifecycle Standard (Sprint 11)
**Date:** 2026-07-27
**Status:** Accepted
**Context:** SunTrack's notification dispatches (WhatsApp, Email, In-App) were previously fire-and-forget with no delivery tracking, making it impossible for admins to audit notification failures, retry failed messages, or correlate delivery issues with business outcomes.
**Decision:**
1. **notification_logs Table:** Create a dedicated `notification_logs` table with a 6-status lifecycle: `pending → processing → sent → delivered → failed → cancelled`. All statuses are represented in the schema from day one even if not all are currently activated by gateway integrations.
2. **NotificationLog Model with State Machine:** Implement typed transition methods (`markProcessing()`, `markSent()`, `markDelivered()`, `markFailed()`, `markCancelled()`) to enforce valid state transitions and prevent invalid status assignments.
3. **Retry Guard:** `canRetry()` method checks that the notification is in `failed` status and that `attempts < max_attempts` (default: 3). Retry resets status to `pending` for queue re-pickup.
4. **Polymorphic notifiable Relationship:** The `notification_logs` table uses `nullableUuidMorphs('notifiable')` to allow any model (Promotion, Campaign, etc.) to be linked as the source that triggered the notification.
5. **NotificationCenterController:** Exposes list (with type/status/date filters), show, retry, cancel, and summary endpoints.
**Rationale:** Delivery tracking transforms notifications from a black box into an auditable operational record. The 6-status lifecycle future-proofs the model for advanced gateway integrations (WhatsApp read receipts, email bounce webhooks) without schema migrations.
**Related Components:** Notifications, NotificationLog, NotificationCenterController, Audit

---

### ADR-030
### Title: Sprint 11 Architecture Approval — Enterprise Intelligence Foundation
**Date:** 2026-07-27
**Status:** Accepted
**Context:** Sprint 11 delivered 7 enterprise intelligence modules: Global Search Engine (Driver Pattern), Enterprise Audit Dashboard, Notification Center (6-status lifecycle), System Monitoring Dashboard (7 categories), Advanced Pricing Analytics & Margin Simulation, Saved Filters & Dashboard Personalization, and Business Intelligence Foundation.
**Decision:** Accept the following as production-standard architectural patterns:
- **SearchDriverInterface** as the mandatory interface for all search engine integrations. Direct Eloquent search in Controllers is prohibited.
- **AnalyticsRepository** as the mandatory data access layer for BI aggregations. Direct `DB::` or `Model::` in Controllers for analytics is prohibited.
- **NotificationLog 6-Status Lifecycle** as the delivery tracking standard for all notification channels.
- **UserPreference + SavedFilter** models as the foundation for all future dashboard personalization features.
- **MetricsService** as the observability abstraction layer. Direct `cache()->get()` for metric storage is prohibited.
- **5 New Technical Documentation Files** (`docs/SEARCH.md`, `docs/MONITORING.md`, `docs/ANALYTICS.md`, `docs/AUDIT.md`, `docs/REPORTING.md`) as living documentation updated on every sprint.
**Benchmark Regression Results at Approval (Sprint 11):**
- Dashboard Cold Cache: **83.14 ms** (SLA: ≤ 100ms) ✅
- Dashboard Warm Cache: **1.78 ms** (SLA: ≤ 15ms) ✅
- Product Catalog Search: **11.24 ms** (SLA: ≤ 50ms) ✅
- Promotion Listing: **10.93 ms** (SLA: ≤ 50ms) ✅
- Campaign Listing: **12.49 ms** (SLA: ≤ 50ms) ✅
- **Average System Latency: 23.92 ms** | **All SLAs: 100% Pass**
- **Feature Test Suite: 25/25 passing, 102 assertions** (Sprint 11 added 18 new tests)
**Rationale:** Sprint 11 transforms SunTrack from an Operational Command Center into an Enterprise Intelligence Platform. All new modules follow Clean Architecture, Repository Pattern, Redis Tag Cache, and zero breaking changes.
**Related Components:** Architecture, Search, Notifications, Monitoring, Analytics, Personalization, BI, CI/CD
