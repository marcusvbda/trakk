# Trakk

Personal goal tracker: goals, daily actions worth points, pace against deadlines, a dense dark dashboard, Telegram reminders and an MCP server for Claude.

![Dashboard](docs/design/reference/dashboard-desktop.png)

## Docs

- [Product](docs/PRODUCT.md): scope, glossary, business rules
- [Architecture](docs/ARCHITECTURE.md): stack, layers, data model, MCP, reminders
- [Design](docs/DESIGN.md): tokens, components, layout; prototypes in `docs/design/prototype/`
- [Roadmap](docs/ROADMAP.md): milestones and setup steps
- [Deploy](docs/DEPLOY.md): VPS plan
- [CLAUDE.md](CLAUDE.md): instructions for coding agents

## Local development

Requires PHP 8.4, Composer, Node 22+, PostgreSQL 17.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
composer run dev   # server, queue worker, scheduler, logs and Vite: everything the app needs
```

## Telegram reminders

1. Create a bot with [@BotFather](https://t.me/BotFather) and put the token in `.env` as `TELEGRAM_BOT_TOKEN`.
2. Add the bot to your chat or group, run `php artisan telegram:link` and send `/start` to the bot: the chat id is saved (groups work too).
3. Press **send test** in Settings to confirm delivery. `composer run dev` runs the scheduler and the queue worker that send reminders.

## Email reminders (Resend)

Email goes through Resend's SMTP, so no extra package is needed. In `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=<your Resend API key>
MAIL_FROM_ADDRESS=<an address on a domain verified in Resend>
```

Without a verified domain (local development) use `MAIL_FROM_ADDRESS=onboarding@resend.dev`: Resend only delivers that sender to the email of the Resend account, so `OWNER_EMAIL` must be that address. Never commit the API key; it lives only in `.env`.

## MCP server (Claude)

The local server is named `trakk` and acts for the owner account (`OWNER_EMAIL`).

```bash
# Claude Code
claude mcp add -s project trakk -- php artisan mcp:start trakk
```

Claude Desktop: add this to `claude_desktop_config.json` (use the absolute path to the project):

```json
{
  "mcpServers": {
    "trakk": {
      "command": "php",
      "args": ["/absolute/path/to/trakk/artisan", "mcp:start", "trakk"]
    }
  }
}
```

Tools: `get-today`, `list-goals`, `complete-task`, `uncomplete-task`, `create-task`, `move-task`, `create-goal`, `replan-tasks`. Inspect them with `php artisan mcp:inspector trakk`.
