# Trakk — Deploy (plan)

Target: the owner's existing VPS, no new paid services. Implemented in milestone M6; this file is the plan the Dockerfiles must follow.

## Services (`compose.yaml`)

| Service | Image / build | Role |
| --- | --- | --- |
| `app` | `docker/app.Dockerfile` (PHP 8.4 FPM, extensions: pdo_pgsql, intl, bcmath, opcache, pcntl) | Serves Laravel |
| `web` | `caddy:2` with `docker/Caddyfile` | HTTPS (automatic certificates), static assets, proxies PHP to `app` |
| `queue` | same image as `app` | `php artisan queue:work --tries=3 --max-time=3600` |
| `scheduler` | same image as `app` | `php artisan schedule:work` |
| `db` | `postgres:17-alpine` | Database, named volume `pgdata`, not exposed publicly |

## Image build

- Multi-stage: `composer install --no-dev --optimize-autoloader` stage, `node` stage for `npm ci && npm run build`, final PHP stage copies `vendor/` and `public/build/`.
- On start: `php artisan migrate --force`, `php artisan optimize`.
- Runs as a non-root user. `.env` is mounted from the server, never baked into the image.

## Domain

A subdomain of a domain the owner already has (e.g. `trakk.<domain>`), A record to the VPS. Caddy issues the certificate.

## CI/CD (GitHub Actions, free tier)

1. On push: install, `php artisan test`, `vendor/bin/phpstan analyse`, `npm run types`.
2. On `main`: SSH to the VPS, `git pull`, `docker compose build`, `docker compose up -d`.

## Backups

Nightly `pg_dump` from a cron job on the host into a dated file, keeping the last 14. Copy offsite later.

## Remote MCP

After Passport is installed, `https://trakk.<domain>/mcp` is added in Claude as a custom connector. OAuth routes come from `Mcp::oauthRoutes()`; dynamic client registration is rate limited.
