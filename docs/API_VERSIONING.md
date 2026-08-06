# SunTrack — API Versioning & Governance Specification

This document defines the strict API versioning, backward compatibility, and response formatting governance enforced across the SunTrack Enterprise platform.

---

## 1. Standardized URL Prefixing & Routing

All HTTP REST API endpoints exposed by SunTrack MUST be prefixed with the API version number in the URI path:

```
https://suntrack.yourdomain.com/api/v1/{resource}
```

- **Current Stable Version:** `v1` (Defined in `routes/api.php` under `prefix('v1')`).
- **Namespace Alignment:** Controllers handling Version 1 endpoints reside in `App\Http\Controllers\Api\V1\`.
- **Public vs. Protected Routes:**
  - Protected Admin endpoints require Sanctum authentication (`auth:sanctum`) and RBAC middleware (`role_or_permission`).
  - Public Brand Collaboration endpoints (`/api/v1/public/reviews/{token}`) rely on secure 64-character token validation and rate limiting.

---

## 2. Backward Compatibility & Zero Breaking Changes Rule

To prevent client application disruption (Vue SPA, mobile clients, external integration partners), SunTrack enforces a **Zero Breaking Changes** policy within a major API version (`v1`).

### What Constitutes a Breaking Change (PROHIBITED in v1):
1. Removing an existing endpoint, HTTP method, or route parameter.
2. Removing a JSON property from an API response payload.
3. Renaming existing JSON properties or database fields exposed in Resource classes.
4. Changing a field data type (e.g., changing a string ID to an integer, or an array to an object).
5. Adding mandatory (required) validation rules to existing endpoint request payloads without fallback defaults.

### Permissible Additive Changes (ALLOWED in v1):
1. Adding new REST endpoints under `/api/v1/`.
2. Adding new optional query parameters or request body properties.
3. Adding new properties to existing JSON response payloads (client parsers must ignore unrecognized keys).
4. Adding new relationships to Eloquent Resource responses when requested via eager loading or query filters.

---

## 3. Deprecation Lifecycle & Versioning Transition

If major architectural requirements necessitate a breaking change, a new major version (`v2`) must be established under `/api/v2/` and `App\Http\Controllers\Api\V2\`. The previous version (`v1`) enters a strict deprecation lifecycle:

1.  **Announcement & Documentation (Month 0):** The endpoint is marked as deprecated in `api_documentation.md` and OpenAPI schemas.
2.  **HTTP Deprecation Headers (Months 1 to 6):** All responses from deprecated endpoints MUST include RFC 8594 standard headers:
    ```http
    Deprecation: true
    Link: <https://suntrack.yourdomain.com/docs/api/v2>; rel="deprecation"; type="text/html"
    Sunset: Sun, 31 Dec 2026 23:59:59 GMT
    ```
3.  **Audit Logging:** Calls to deprecated endpoints are logged via `ActivityLogger` with category `System:ApiDeprecationWarning` to alert administrators of clients requiring migration.
4.  **Decommissioning (After Sunset Date):** The endpoint is removed, returning HTTP `410 Gone`.

---

## 4. Standardized JSON Response Contracts

All API endpoints MUST serialize responses using the `App\Traits\ApiResponse` trait to guarantee consistent JSON structure across the entire platform.

### A. Success Response Contract (`HTTP 200 OK`, `201 Created`)
```json
{
  "success": true,
  "message": "Promotion retrieved successfully.",
  "data": {
    "promotion": {
      "id": "9d3c5f21-8e3b-4a11-b23d-6c1f9c3a8b41",
      "code": "PROMO-2026-001",
      "name": "Summer Mega Sale",
      "status": "Pending",
      "created_at": "2026-07-27T10:00:00+07:00"
    }
  }
}
```

### B. Validation Error Response Contract (`HTTP 422 Unprocessable Entity`)
```json
{
  "success": false,
  "message": "Validation error.",
  "errors": {
    "campaign_price": [
      "The campaign price must be greater than or equal to bottom price."
    ],
    "discount_price": [
      "The discount price field is required."
    ]
  }
}
```

### C. Security & Authorization Error Contracts (`HTTP 401 Unauthenticated`, `403 Unauthorized`)
```json
{
  "success": false,
  "message": "User does not have the right roles or permissions.",
  "errors": []
}
```

---

## 5. Pagination & Filtering Standards

All list endpoints (`index` methods) MUST return paginated datasets using Laravel's native LengthAwarePaginator serialized through Eloquent Resources:
- **Default Page Size:** `15` items per page.
- **Client Override:** Clients may request custom pagination via query parameter `?per_page=50` (capped at a maximum of `100` items per request to prevent database memory exhaustion).
- **Metadata Included:** Paginated responses automatically wrap items inside `data` while providing pagination details (`current_page`, `last_page`, `per_page`, `total`) at the root level.
