# Sumbungan REST API

Base URL: same host as the app, paths relative to the project root (for example `/Sumbungan/api/` when the project lives under `htdocs/Sumbungan`).

All JSON responses use this envelope:

```json
{ "ok": true, "data": { }, "error": null }
```

On failure: `ok` is `false`, `data` is usually `null`, and `error` is a short message. HTTP status codes: `400` validation, `401` not logged in, `403` wrong role, `404` not found, `405` method not allowed, `409` conflict, `500` server error.

## Authentication

Session cookies (`PHPSESSID`) are set on successful login. Send cookies with `credentials: 'same-origin'` from JavaScript.

- **Complainant** may access complainant-scoped complaint and notification endpoints.
- **Admin** may access all complaint endpoints, analytics, user list, and complaint updates.

## Endpoints

### `POST /api/auth.php`

Login, register, or logout. Action is determined in this order: query `?action=`, form/body field `action`, or inferred from body (`login` if `email` + `password` only; `register` if `full_name` is also present).

**Login**

- Body (JSON or form): `email`, `password`
- Success `200`: `data.user`, `data.role`

**Register**

- Body: `full_name`, `email`, `password` (min 8 characters), optional `address`
- Success `200`: `data.user`
- `409`: duplicate email

**Logout**

- Query or body: `action=logout`
- Success `200`: `data.logged_out`

---

### `POST /api/upload.php`

Upload an image (complaint attachment). Requires login.

- `multipart/form-data` field: `file`
- Allowed: JPEG, PNG, WebP, GIF; max 5 MB
- Success `200`: `data.path` (relative to `assets/`, e.g. `uploads/abc.jpg`), `data.url` (full browser URL to the file)

---

### `GET|POST|PATCH /api/complaints.php`

Requires login.

**List — `GET /api/complaints.php`**

- Complainant: own complaints only.
- Admin: all complaints; optional filters `status`, `type`, `q` (search code, location, complainant name).
- Success `200`: `data.items` — array of complaint objects (`code`, `code_display`, `type`, `location`, `status`, dates; admin rows include `complainant_id`, `complainant_name`).

**Detail — `GET /api/complaints.php?id=BRY-4021`**

- `id` may include a leading `#`; it is normalized to the stored `code`.
- Complainant may only load their own cases.
- Success `200`: complaint object plus `timeline` (array of `status_label`, `details`, `created_at`).

**Create — `POST /api/complaints.php`**

- JSON: `type` (`Noise` | `Sanitation` | `Security` | `Traffic` | `Other`), `location`, `description`, optional `photo_path` (from `/api/upload.php`).
- Or `multipart/form-data` with the same fields plus optional file field `photo`.
- Success `201`: created complaint summary.

**Update — `PATCH /api/complaints.php?id=BRY-4021`**

- Admin only.
- Body (JSON or form): optional `status` (`pending` | `in-progress` | `resolved` | `rejected`), optional `timeline_label` and `timeline_details` (appends a timeline row when label is non-empty).
- Override: `POST` with `_method=PATCH` or header `X-HTTP-Method-Override: PATCH` is treated as PATCH when implemented by the server (this deployment uses true `PATCH` or form body on PATCH).

---

### `GET /api/analytics.php`

Admin only.

| Query `metric` | Description |
|----------------|---------------|
| `summary` (default) | Totals by status, resolution %, resident count |
| `daily` | Last 14 days: `labels`, `values` (counts per day) |
| `by_type` | `items`: `{ type, c }` counts |
| `by_status` | Stacked chart series: `labels`, `pending`, `in_progress`, `resolved`, `rejected` arrays by week |
| `top_locations` | `items`: `{ location, c }` |
| `recent` | `items`: last 5 cases with `code`, `type`, `status`, `created_at`, `complainant_name` |

---

### `GET|POST /api/users.php`

**`GET /api/users.php`** — Admin only. Returns `data.items`: complainant users (`id`, `full_name`, `email`, `address`, `created_at`).

**`POST /api/users.php?action=profile`** — Logged-in user updates own profile.

- Form or JSON: `full_name` (optional but typical)
- Multipart: optional `profile_pic` image (JPEG/PNG/WebP, max 2 MB)
- Success `200`: updated user row (`id`, `full_name`, `email`, `role`, `profile_pic`)

---

### `GET /api/export.php`

Admin only. Returns a CSV download.

- `?type=cases` (default) — supports filters `status`, `type`, `q` (same as `/api/complaints.php`).
- `?type=users` — exports the resident roster with case counts.

Response: `text/csv` attachment.

---

### `GET|POST /api/notifications.php`

Requires login. Returns notifications for the current user.

**`GET /api/notifications.php`**

- Success `200`: `data.items` — `id`, `message`, `is_read`, `created_at`

**`POST /api/notifications.php?action=read`**

- Body optional: `id` (mark one read) or omit to mark all as read.
- Success `200`: `data.ok`

---

## Errors

| HTTP | Meaning |
|------|---------|
| 400 | Missing or invalid parameters |
| 401 | Not authenticated |
| 403 | Authenticated but not allowed for this resource |
| 404 | Resource not found |
| 405 | HTTP method not supported for this route |
| 409 | Duplicate or conflict (e.g. email exists) |
| 500 | Unexpected server error |
