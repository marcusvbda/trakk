# Trakk — Deploy (plan)

Target: the owner's existing VPS, no new paid services. Implemented in milestone M6; this file is the plan the Dockerfile and `compose.yaml` must follow.

## Web server: FrankenPHP

FrankenPHP is a PHP application server built on Caddy. It replaces both PHP-FPM and a separate web server, so there is one container for HTTP.

- Image: `dunglas/frankenphp` (PHP 8.4), extended in `docker/Dockerfile` with the extensions `pdo_pgsql`, `intl`, `bcmath`, `opcache` and `pcntl` (via `install-php-extensions`).
- **Classic mode**, not worker mode: every request boots Laravel fresh, exactly like PHP-FPM. Laravel Octane is not installed, so the code has no worker-state constraints. Worker mode (Octane) is a later, separate decision.
- `docker/Caddyfile` sets the site address, `root * /app/public`, `php_server`, and encodes responses. FrankenPHP issues and renews the HTTPS certificate automatically (ports 80, 443 and 443/udp published).
- Static assets in `public/build` are served by Caddy directly, with long cache headers for hashed files.
- The app needs no trusted-proxy configuration: TLS terminates in the same process.

## Services (`compose.yaml`)

| Service | Image / build | Role |
| --- | --- | --- |
| `app` | `docker/Dockerfile` (FrankenPHP, PHP 8.4) | Serves Laravel over HTTPS |
| `queue` | same image as `app` | `php artisan queue:work --tries=3 --max-time=3600` |
| `scheduler` | same image as `app` | `php artisan schedule:work` |
| `db` | `postgres:17-alpine` | Database, named volume `pgdata`, not exposed publicly |

`queue` and `scheduler` mirror the processes that `composer run dev` starts locally. When a new process is added to `DevCommands` in `AppServiceProvider`, add the matching service here.

## Image build

- Multi-stage: `composer install --no-dev --optimize-autoloader` stage, `node` stage for `npm ci && npm run build`, final FrankenPHP stage copies `vendor/` and `public/build/`.
- On start of `app`: `php artisan migrate --force`, `php artisan optimize`. `queue` and `scheduler` start only after `app` is healthy.
- Runs as a non-root user. `.env` is mounted from the server, never baked into the image.

## Domain

A subdomain of a domain the owner already has (e.g. `trakk.<domain>`), A record to the VPS. FrankenPHP issues the certificate.

## CI/CD (GitHub Actions, free tier)

1. On push: install, `php artisan test`, `vendor/bin/phpstan analyse`, `npm run types`.
2. On `main`: SSH to the VPS, `git pull`, `docker compose build`, `docker compose up -d`.

## Backups

Nightly `pg_dump` from a cron job on the host into a dated file, keeping the last 14. Copy offsite later.

## Remote MCP

After Passport is installed, `https://trakk.<domain>/mcp` is added in Claude as a custom connector. OAuth routes come from `Mcp::oauthRoutes()`; dynamic client registration is rate limited.
