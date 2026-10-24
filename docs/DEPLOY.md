# Trakk — Deploy

Target: the owner's Hostinger VPS at `trakk.mvbassalobre.com`, no new paid services. The VPS is **shared with other projects** (which also live under `/opt`, so Trakk gets its own `/opt/trakk`): nothing here may touch containers, volumes, ports, files or crontab entries that are not Trakk's.

Every push to `main` runs the tests and, if they pass, deploys with zero downtime.

## Architecture

```
internet ──443──▶ public Caddy of the VPS ──trakk_edge──▶ app (FrankenPHP, 1..2 containers, :8080)
 (wpp-automation-n8n project)                              queue      (same image)
                                                           scheduler  (same image)
                                                           db         (postgres:17-alpine)
```

| Service | Image | Role |
| --- | --- | --- |
| `app` | `docker/Dockerfile` (FrankenPHP, PHP 8.4) | Serves Laravel over plain HTTP on 8080 (never published), runs migrations on start (`RUN_MIGRATIONS=true`). Joins the external network `trakk_edge` with the alias `trakk-app` |
| `queue` | same image | `php artisan queue:work --tries=3 --max-time=3600`, 60s stop grace period |
| `scheduler` | same image | `php artisan schedule:work` |
| `db` | `postgres:17-alpine` | Database, volume `pgdata`, not exposed |

Trakk has **no proxy of its own**: ports 80/443 on this VPS belong to the Caddy of the `wpp-automation-n8n` project, which terminates HTTPS (certificates are issued automatically) and, thanks to the block in `docker/Caddyfile.edge`, forwards `trakk.mvbassalobre.com` to `trakk-app:8080`. It re-resolves the alias every 5s and retries another container while one is being replaced, which is what makes the rollout zero-downtime.

- FrankenPHP runs in **classic mode** (one fresh PHP request per hit, like PHP-FPM). Octane is not installed.
- `docker/Caddyfile` is the app's plain-HTTP config (`auto_https off`, `admin off`, long cache for `/build/assets/*`, only for files that exist: never send the `immutable` header on a 404, or a missing hash during a rolling deploy stays cached in the browser and leaves the page blank until the cache is cleared).
- The app trusts the proxy (`trustProxies(at: '*')`): only containers on `trakk_edge` can reach port 8080.
- The compose project name is fixed to `trakk`, so containers, network and volumes are all prefixed `trakk_` and cannot collide with other projects.
- The image is built from the repository on the VPS (`docker compose build app`). The `.env` lives only on the server and is never baked into the image.

## Zero-downtime rollout (`bin/deploy`)

Runs on the VPS from `/opt/trakk`:

1. `docker compose build app`, then start `db` and wait until it is healthy.
2. Remove any `app` container that is not healthy (leftovers of an earlier failed rollout never block a new one), then start a **new** `app` container next to the healthy one(s) (`--scale app=N+1 --no-recreate`; N is 0 on the first deploy). Its entrypoint runs `migrate --force` and `optimize`, and it must report healthy on `/up` within 3 minutes.
3. Healthy: stop (30s grace) and remove the old container(s). The public Caddy sees the change through DNS within 5s and retries on the other container meanwhile.
4. Not healthy: print the new container's logs, remove it, **keep the old version serving** and exit with an error (the GitHub job fails).
5. Recreate `queue` and `scheduler`, and remove dangling Trakk images only (`--filter label=app=trakk`). `bin/deploy` also creates the `trakk_edge` network when it is missing.

### Migrations must be backward compatible

The old release keeps serving while the new one migrates (and keeps serving after a failed rollout), so a migration may never break the code of the previous release. Use expand/contract: add the new column or table first, deploy code that uses it, remove the old shape in a later release. This is a rule in `.ai/rules/models.md`.

### Rollback

A failed rollout rolls back by itself (step 4). To go back to an earlier version after a successful deploy, revert the commit on `main`; the pipeline deploys the revert. Migrations are not reverted automatically; write a forward migration.

### Shared VPS: what Trakk touches

Only `/opt/trakk`, `/opt/trakk-backups` (mode 700), `/opt/trakk-edge`, the `trakk_*` Docker resources and one crontab line tagged `# trakk-backup`. Container logs are capped (10 MB × 3 files per container).

The image is built on the VPS (`nice -n 19`), which uses CPU, RAM and disk (the BuildKit cache grows over time; clean it by hand with `docker builder prune` when the disk gets tight, knowing that cache is shared with the other projects). If this becomes a problem, build in CI and push to GHCR instead.

### Public proxy (one-time, on the VPS)

The n8n project deploys with `git pull` and **aborts when a tracked file has local changes**, so its `Caddyfile` and `docker-compose.yml` must never be edited. Trakk plugs in through two untracked pieces instead:

1. `/opt/trakk-edge/Caddyfile`: imports the n8n Caddyfile unchanged, then adds Trakk's block.

   ```bash
   mkdir -p /opt/trakk-edge
   { echo 'import /etc/caddy/Caddyfile'; echo; cat /opt/trakk/docker/Caddyfile.edge; } > /opt/trakk-edge/Caddyfile
   ```

2. `/opt/wpp-automation-n8n/docker-compose.override.yml` (untracked; `docker compose` loads it automatically, also in n8n's own deploys): joins its Caddy to `trakk_edge` and starts it from the wrapper.

   ```yaml
   services:
     caddy:
       command: ["caddy", "run", "--config", "/etc/caddy/edge/Caddyfile", "--adapter", "caddyfile"]
       volumes:
         - /opt/trakk-edge:/etc/caddy/edge:ro
       networks:
         - default
         - trakk_edge

   networks:
     trakk_edge:
       external: true
   ```

Then validate and apply: `docker network create trakk_edge` (once; `bin/deploy` also does it), `cd /opt/wpp-automation-n8n && docker compose run --rm --no-deps caddy caddy validate --config /etc/caddy/edge/Caddyfile --adapter caddyfile`, and `docker compose up -d --no-deps caddy` (a few seconds of pause for n8n while its Caddy is recreated). Afterwards, changes to `docker/Caddyfile.edge` are applied by regenerating the wrapper and running `docker exec wpp-automation-n8n-caddy-1 caddy reload --config /etc/caddy/edge/Caddyfile --adapter caddyfile`.

## CI/CD (`.github/workflows/tests.yml`)

- `ci` (push and pull request): PHP 8.4, `postgres:17` service (trust auth, databases `trakk` and `trakk_test`), Node 22, Playwright Chromium for the Browser suite, then `composer ci:check` (lint, types, Pint, PHPStan, tests).
- `deploy` (only push to `main`, `needs: ci`): its own `concurrency` group that never cancels a running deploy, `permissions: contents: read`. It writes the SSH key and `known_hosts` from secrets, runs `rsync -az --delete --inplace` of the code into `/opt/trakk` (excluding `.git`, `node_modules`, `vendor`, `.env`, `storage`, `public/build`, which also protects them from `--delete`), then `ssh ... 'cd /opt/trakk && bin/deploy'`.
- If the tests fail, nothing is deployed.
- Actions are pinned by commit SHA.

**Accepted risk:** the deploy key can run any command as the SSH user, and that user can use Docker, which is root-equivalent on the shared host. Anyone who can push to `main` can run code on the VPS anyway (Dockerfile, `bin/deploy`), so a forced command would add little. Keep write access to the repository and the Actions secrets to the owner, and consider a dedicated deploy user later.

Secrets: `DEPLOY_VPS_HOST`, `DEPLOY_VPS_USER`, `DEPLOY_SSH_KEY`, `DEPLOY_VPS_KNOWN_HOSTS`, and optionally `DEPLOY_VPS_PORT` (default 22). `bin/deploy-secrets` sets all of them.

## One-time setup

The SSH address comes from the local `.env` (`DEPLOY_VPS_HOST`, `DEPLOY_VPS_USER`). The scripts read only those two lines.

1. **`bin/bootstrap-vps`** (local). Installs Docker on the VPS only if missing, creates `/opt/trakk` and `/opt/trakk-backups` (owned by the SSH user, using `sudo` when not root; the VPS firewall already serves 80/443 for the n8n project, so nothing to open), generates the production `.env` from `.env.production.example` (random `APP_KEY` and `DB_PASSWORD`; an existing `.env` is never overwritten) and adds the backup cron to the existing crontab, replacing only its own `# trakk-backup` line. A non-root SSH user needs passwordless `sudo`. It is safe to run again.
2. On the VPS, fill `OWNER_EMAIL`, `OWNER_PASSWORD`, `TELEGRAM_BOT_TOKEN` (and, for email reminders, `MAIL_MAILER=smtp` plus `MAIL_PASSWORD`) in `/opt/trakk/.env`. Email reminders stay in the log until the mailer is set: production sends through Resend over **SMTP** (`MAIL_MAILER=smtp`, `MAIL_HOST=smtp.resend.com`, `MAIL_PORT=587`, `MAIL_USERNAME=resend`, `MAIL_PASSWORD=<Resend API key>`). `MAIL_FROM_ADDRESS` must be a sender on a domain verified in Resend; without one use `onboarding@resend.dev`, which Resend delivers only to the Resend account's own email (`OWNER_EMAIL`). The app caches its config when a container starts, so after editing `.env` run `docker compose up -d --force-recreate queue` (the queue worker sends the emails) and the other containers on the next deploy. Queue and scheduler have their healthcheck disabled: the base image's check targets the web server, which they do not run. Double every `$` in a value (`$$`), because compose interpolates `.env`.
3. In the DNS panel for `mvbassalobre.com`, add an **A record** `trakk` pointing to the VPS IP. The domain's nameservers are Hostinger's (`ns1/ns2.dns-parking.com`), **not Vercel's**: Vercel cannot create the record.
4. Connect the public Caddy to Trakk ("Public proxy" above), once the DNS record resolves.
5. **`bin/deploy-secrets`** (local). Generates a throwaway ed25519 key pair, installs the public key in the VPS `authorized_keys` (prefixed with `restrict`), sends the secrets to the GitHub repo with `gh secret set`, records the host key (from your local `known_hosts` when present, otherwise `ssh-keyscan`, whose fingerprint it prints for you to check; the target repository is found from the project directory and printed) and deletes the private key from disk.
6. Push to `main`. After the **first** deploy, seed the owner once:

   ```bash
   ssh <user>@<host> 'cd /opt/trakk && docker compose exec app php artisan db:seed --force'
   ```

   Never seed on later deploys: `updateOrCreate` resets the owner's password to `OWNER_PASSWORD`. Production starts empty; `DemoSeeder` only runs in `local` and `testing`.

## Backups

`bin/backup-db` runs from cron at 03:00 (server time): `pg_dump --clean --if-exists` through `docker compose exec -T db`, gzipped (mode 600) into `/opt/trakk-backups/trakk-<timestamp>.sql.gz`, keeping the last 14. Restore, even over a populated database, with `gunzip -c <file> | docker compose exec -T db psql -U trakk -d trakk`. Copying the files offsite is a later step.

## Remote MCP

`https://trakk.mvbassalobre.com/mcp` is the remote MCP server (Passport OAuth 2.1). The public Caddy forwards every path of the host, including `/.well-known/oauth-*`, `/oauth/*` and `/mcp`, so nothing extra is needed there.

One-time setup after the first deploy that contains Passport:

1. On the VPS, generate the signing keys once: `ssh <user>@<host> 'cd /opt/trakk && bin/passport-keys'`. It appends `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` to `.env` without printing them, refuses placeholder values and never replaces existing keys (replacing them would disconnect every client).
2. Roll the app so it reads them: `ssh <user>@<host> 'cd /opt/trakk && bin/deploy'` (zero-downtime; the image build is cached). Never `--force-recreate app`: that stops the only app container.
3. Check discovery: `curl -s https://trakk.mvbassalobre.com/.well-known/oauth-authorization-server` must answer with JSON that lists the `registration_endpoint`.
4. In Claude (web or mobile), Settings → Connectors → Add custom connector, URL `https://trakk.mvbassalobre.com/mcp`. Claude registers itself, sends you to Trakk, you sign in as the owner, check that the page names the right app and the redirect host (`claude.ai`), and press **authorize**.

Safety rules of the remote server:

- Dynamic client registration is public by specification, so `config/mcp.php` allows only these redirect domains: `claude.ai`, `claude.com`, `chatgpt.com`, `chat.openai.com` and localhost. **Never set `*`**: an attacker could register a client named "Claude" that redirects to their own site and trick the signed-in owner into approving it. Registration is throttled to 10/min.
- Access tokens last 1 day and refresh tokens 30 days, so a connection has to be renewed monthly. `passport:purge` runs daily from the scheduler and removes revoked and expired tokens.
- Only the authorization code flow is enabled (the device flow is off).
- Claude can read goals and today's actions and complete, undo, move, create and replan, but nothing can be deleted. Everything it does is logged with the "claude" mark and lights the `mcp` pill in the top bar.

To disconnect a client, revoke its tokens in Postgres (the refresh tokens too, otherwise the client can ask for a new access token):

```sql
UPDATE oauth_refresh_tokens SET revoked = true WHERE access_token_id IN (SELECT id FROM oauth_access_tokens WHERE client_id = '<client id>');
UPDATE oauth_access_tokens SET revoked = true WHERE client_id = '<client id>';
```
