# Deployment Guide

## Why the Vercel URL downloads PHP
`https://digital-smart-class.vercel.app/` is served by Vercel's **static** hosting.
Vercel does not execute PHP or host MySQL by default, so it returns `index.php`
as a file (`Content-Type: application/x-httpd-php`) instead of running it.

Digital Smart Class is a PHP 8 + MySQL application with file uploads and sessions,
so it needs a PHP runtime and a persistent database/filesystem. Use a PHP host.

## Recommended hosts
| Host | PHP | MySQL | Persistent uploads | Notes |
| :--- | :--: | :---: | :----------------: | :---- |
| Railway | ✅ | ✅ (plugin) | ✅ | Dockerfile auto-detected |
| Render | ✅ | ✅ (external) | ✅ | Use a Dockerfile service |
| Fly.io | ✅ | ✅ (external) | ✅ (volume) | Add a volume at `/var/www/html/uploads` |
| Shared cPanel hosting | ✅ | ✅ | ✅ | Upload files, import SQL in phpMyAdmin |

## Option A — Docker (Railway / Render / Fly.io / VPS)
The repository includes a `Dockerfile` (PHP 8.2 + Apache + `pdo_mysql`).
It serves the app from `/var/www/html` and enables `.htaccess`.

### Local test
```bash
docker build -t digital-smart-class .
docker run --rm -p 8080:80 digital-smart-class
# open http://localhost:8080
```

### Railway
1. Create a new project from the GitHub repository.
2. Add a **MySQL** database plugin.
3. On the web service, add environment variables from `.env.example`
   (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`).
4. Set `DB_IMPORT=1` once to import `database/digital_smart_class.sql`,
   then set it back to `0`.
5. Railway detects the `Dockerfile` automatically.
6. Health check path: `/health.php`.

### Render
1. New → **Web Service** → connect the repository.
2. Runtime: **Docker**.
3. Add a MySQL database (external provider) and set the `DB_*` variables.
4. Health check path: `/health.php`.

### Fly.io
1. `fly launch` (uses the `Dockerfile`).
2. Attach a volume for uploads: `fly volumes create dsc_uploads --size 1`
   and mount it at `/var/www/html/uploads`.
3. Set `DB_*` secrets with `fly secrets set`.
4. `fly deploy`.

## Option B — Shared PHP hosting (cPanel)
1. Upload the project into `public_html` (or a subfolder).
2. Create a MySQL database and user in cPanel.
3. Import `database/digital_smart_class.sql` via phpMyAdmin.
4. Edit `config/database.php` defaults **or** set `DB_*` environment variables.
5. Ensure `uploads/` is writable (`755`/`775`).

## Environment variables
See `.env.example`. The application reads them with `getenv()`:

- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `DB_IMPORT` (Docker only; optional one-time seed import)

`config/database.php` falls back to the local XAMPP defaults
(`localhost` / `root` / empty password) when variables are not set, so local
development keeps working unchanged.

## Persistent uploads
Serverless platforms lose local files on deploy. With Docker hosts, mount a
volume at `/var/www/html/uploads` so teacher uploads survive restarts.

## Post-deploy checklist
- [ ] `/health.php` returns `"status":"ok"` and `"database":"connected"`
- [ ] Homepage renders styled content (not raw PHP)
- [ ] `assets/css/style.css` loads
- [ ] Demo login works for all four roles
- [ ] Uploaded course media streams through `student/file.php`

## Troubleshooting

### `AH00534: apache2: Configuration error: More than one MPM loaded.`
Apache refuses to start when two Multi-Processing Modules are enabled at once.
The `php:8.2-apache` base image already disables `mpm_event`, so if you see this
the platform is **not** running the repository `Dockerfile`.

Fixes:
1. Force the Docker builder. On Railway this is done by `railway.json`
   (`"builder": "DOCKERFILE"`), which is included in this repository.
   Redeploy after clearing the build cache.
2. The container entrypoint also disables `mpm_event`/`mpm_worker` and enables
   `mpm_prefork` at startup, so a correct image self-heals.

### The container starts but the platform cannot reach it
Hosting platforms inject a `PORT` variable. The entrypoint rewrites Apache's
`ports.conf` and virtual host to listen on `$PORT` automatically, so no manual
port configuration is required.

### Database not connected
Set the `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` variables.
Check `/health.php`; it reports `"database":"connected"` once the app can reach
MySQL.

## Retire the static Vercel deployment
Once a PHP host is live, delete or unpublish the Vercel project (or keep it
purely as a static marketing page) so visitors do not download the PHP source.
