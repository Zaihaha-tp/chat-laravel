# AGENTS.md — Laravel_Try (TempChat)

Stock Laravel 13 skeleton (PHP ^8.3, running 8.4) + Tailwind v4 + Vite. No app code yet — only default route, `User` model, default migrations. The real spec lives in `.agents/`.

## Must-read before any task

- `.agents/prd.md` — full TempChat spec (rooms, polling, typing, expiry, security)
- `.agents/design.md` — Zed Dev tokens: near-black `#161614`, surface `#1D1D1B`, single accent cyan `#5FB5D6`, JetBrains Mono + Inter, flat (no gradients), one accent action per screen
- `.agents/task-instruction.md` — after each task group, update `.agents/tasklist.md` (`[x] ✅`, progress %, files touched) before reporting

## Commands (use these, not guesses)

- First setup: `composer setup` (install + `.env` copy + key + migrate + `npm install` + `npm run build`)
- All-in-one dev: `composer dev` (serve + `queue:listen` + `pail` + `vite` via concurrently)
- Tests: `composer test` (runs `config:clear` + `artisan test`); single file: `php artisan test --filter=RoomTest` or `php artisan test tests/Feature/RoomTest.php`
- Lint: `./vendor/bin/pint --test` to check, `./vendor/bin/pint` to fix (no `pint.json`, defaults apply)
- Migrations: `php artisan migrate`, fresh: `php artisan migrate:fresh --seed`
- Frontend: `npm run dev` (HMR) vs `npm run build` (production to `public/build`, gitignored)

## DB / env gotcha

- `.env` currently points to MySQL (`DB_CONNECTION=mysql`, DB `chat-laravel`, empty password) per `prd.md`; `phpunit.xml` overrides to sqlite `:memory:` for tests — tests never need MySQL.
- `.env.example` defaults to sqlite. A `database/database.sqlite` file already exists for local sqlite fallback. Never commit `.env`.

## Where code goes

- Routes: `routes/web.php` (currently only `/` → `welcome` view), console/scheduler: `routes/console.php`
- Standard layout: `app/Http/Controllers/`, `app/Models/` (`User.php` only so far), `database/migrations/`, Blade in `resources/views/`, Vite entries `resources/css/app.css` + `resources/js/app.js` (see `vite.config.js`), axios already in `package.json`.

## PRD constraints agents miss

- Room code: 8 chars, uppercase A–Z + 0–9, collision-resistant; URL `/room/{code}`; `expired_at = now + 24h`; FK cascade deletes + scheduler/command cleanup.
- Typing: `POST /room/{code}/typing` updates `typing_at` only on real typing with 300ms debounce (not per keystroke); polling every 2s must hit read-only `GET /room/{code}/typing/status` that never writes; active = `typing_at >= now() - 3s`; hide self, auto-hide ≤5s.
- Presence: `last_seen` updated on activity/message/typing; online = `last_seen` within 30s. Message poll: AJAX every 2s.
- Validation: name max 30, message max 500; dedup names per room with ` (2)` suffix; 1 msg/sec/user rate limit; escape/sanitize in Blade + JSON (XSS); CSRF on all AJAX; system messages on join/leave.
- Check room expiry in middleware; countdown turns warning color at <1h remaining.
