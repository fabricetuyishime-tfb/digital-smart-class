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
The repository includes a `Dockerfile` that runs **PHP 8.2 with its built-in
server** (no Apache) plus `pdo_mysql`. `docker/router.php` reproduces the
`.htaccess` rules: it blocks `config/`, `includes/`, `database/`, `docker/`,
`uploads/`, and source/backup files, then serves pages and assets.

Using the built-in server removes Apache's MPM configuration entirely, so
`AH00534: More than one MPM loaded` cannot occur with this image.

### Local test
```bash
docker build -t digital-smart-class .
docker run --rm -p 8080:80 digital-smart-class
# open http://localhost:8080
```

### Railway
1. Create a new project from the GitHub repository (branch `main`).
2. Add a **MySQL** database plugin (New → Database → MySQL).
3. On the **web service**, open **Variables** and add references to the MySQL
   service (Railway substitutes these at runtime):
   ```
   DB_HOST = ${{MySQL.MYSQLHOST}}
   DB_PORT = ${{MySQL.MYSQLPORT}}
   DB_NAME = ${{MySQL.MYSQLDATABASE}}
   DB_USER = ${{MySQL.MYSQLUSER}}
   DB_PASS = ${{MySQL.MYSQLPASSWORD}}
   DB_IMPORT = 1
   ```
   Alternatively, reference the whole connection string once:
   ```
   MYSQL_URL = ${{MySQL.MYSQL_URL}}
   ```
   `config/database.php` understands both forms plus the raw Railway names
   (`MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`).
4. `railway.json` (already in the repo) pins the **Dockerfile** builder — this is
   what prevents the `AH00534: More than one MPM loaded` crash. Redeploy after
   pushing, and choose **Clear build cache**.
5. `DB_IMPORT=1` imports `database/digital_smart_class.sql` on first boot; the
   entrypoint skips the import once tables exist, so it is safe to leave on.
6. Health check path: `/health.php` (already configured in `railway.json`).

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
6. Raise PHP upload limits in cPanel → **MultiPHP INI Editor** (or `php.ini`):
   `upload_max_filesize = 512M`, `post_max_size = 512M`, `memory_limit = 512M`.

## Media upload limits
Course videos need more than PHP's default 2M upload cap. The Docker image applies
[`docker/php.ini`](../docker/php.ini) automatically (copied to `conf.d`). On other
hosts, set the same values there. When running the PHP built-in server locally, pass
them on the command line:

```bash
php -d upload_max_filesize=512M -d post_max_size=512M -d memory_limit=512M -S localhost:8000 -t .
```

## Environment variables
See `.env.example`. The application reads them with `getenv()`:

- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `MYSQL_URL` / `DATABASE_URL` (a `mysql://user:pass@host:port/db` string)
- `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`
  (Railway's MySQL plugin variable names)
- `DB_IMPORT` (Docker only; imports the seed on first boot, then skips)
- `PORT` (injected by the host; the server is started on this port)

Explicit `DB_*` values win. If they are absent, the app falls back to the
Railway variables or connection string, then to the local XAMPP defaults
(`localhost` / `root` / empty password) so local development keeps working.

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
This error comes from Apache, but this project's `Dockerfile` **does not use
Apache at all** (it uses PHP's built-in server). Seeing this message therefore
means the platform is running its own Apache build and ignoring the `Dockerfile`.

Fix it by making the platform use the repository Dockerfile:
1. Railway → service → **Settings → Build** → set **Builder** to `Dockerfile`
   (the included `railway.json` already requests this, but a service created
   before the Dockerfile existed can keep its old builder). Then **Redeploy**
   with **Clear build cache**.
2. If the builder cannot be changed, **delete the service and create it again**
   from the repository so the Dockerfile is detected on first build.
3. In the build log you should now see `FROM php:8.2-cli` (not an Apache image)
   and, at runtime, `Starting PHP built-in server on port ...`.

### The container starts but the platform cannot reach it
Hosting platforms inject a `PORT` variable. The entrypoint starts the built-in
server on `$PORT` automatically, so no manual port configuration is required.

### Database not connected
Set the `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` variables.
Check `/health.php`; it reports `"database":"connected"` once the app can reach
MySQL.

## Retire the static Vercel deployment
Once a PHP host is live, delete or unpublish the Vercel project (or keep it
purely as a static marketing page) so visitors do not download the PHP source.
