# Changes in this round

## 1. Realtime chat fix (the actual bug)

The dashboard's Echo client was calling Laravel's **default** `/broadcasting/auth`
route (auto-registered when `channels:` is passed to `withRouting()` in
`bootstrap/app.php`). That default route runs on the **web/session guard** — but
the dashboard authenticates every other request with a **Sanctum bearer token**,
so that route never recognized a logged-in agent and every private-channel
subscription (`site.{id}.queue`, `chat.session.{token}`) silently failed to
authorize. That's why realtime updates weren't arriving.

**Fix:** added `App\Http\Controllers\Api\V1\AgentBroadcastAuthController`
(mirrors the `WidgetBroadcastAuthController` you already had for visitors),
sitting behind `auth:sanctum` at `POST /api/v1/broadcasting/auth`, and pointed
`dashboard/src/services/echo.js` at it instead of the default route.

**Also fixed as part of the same investigation:**
- **Duplicate messages** — a sent message was pushed to the UI locally *and*
  re-delivered by the broadcast (since nothing was excluding the sender's own
  socket). Fixed with id-based de-duplication (`chats.appendMessage()` /
  the equivalent in the widget's `ChatPanel.vue`), rather than wiring up
  Pusher's `X-Socket-Id` header end to end.
- **The 409-on-closed-session bug you'd already worked around by commenting
  out code** — `WidgetMessageController` was rejecting visitor messages to a
  closed session, which directly contradicted `ChatMessageObserver`'s
  "a visitor message reopens a closed conversation" behavior. Removed the
  check properly instead of leaving it commented out.
- `ChatSessionResource.messages` now always resolves to an array instead of
  being silently omitted when the relation isn't eager-loaded.

**Nothing else to configure** — this is a code fix, not an env/config change.

### 1b. Follow-up: 419 CSRF mismatch on `/broadcasting/auth`

After the fix above, subscribing still failed — this time with `419 CSRF
token mismatch`. Cause: your dashboard origin (`localhost:5173`) is in
`SANCTUM_STATEFUL_DOMAINS`, so Sanctum's `EnsureFrontendRequestsAreStateful`
middleware requires the session cookie + CSRF token on every `/api/*`
request from that origin — which is how your login/reply/claim calls
already work. Pusher-js's own built-in auth request doesn't know about
that cookie/header the way our `api` axios instance does, so it never had
a chance.

**Fix:**
- `dashboard/src/services/echo.js` now authorizes channels through a custom
  `authorizer` that calls `/v1/broadcasting/auth` via the same `api` axios
  instance every other (working) request uses — cookies + CSRF header get
  attached automatically, same as `/v1/me` or `/v1/chats/{id}/reply`.
- `bootstrap/app.php` exempts `api/v1/broadcasting/auth` and
  `api/v1/widget/broadcasting/auth` from CSRF verification outright, since
  neither endpoint's own security depends on the CSRF token (one checks the
  Sanctum session guard inside the controller, the other a signed
  `site_key`) — this also fixes a latent version of the same issue for the
  widget when testing it on `localhost`/`127.0.0.1`, which are also in the
  stateful domains list.

## 2. Document / image upload

New migration adds `attachment_path/name/mime/size` to `chat_messages`.
Agents and visitors can each attach one file per message (10MB limit,
common doc + image mime types) via:

- `POST /api/v1/chats/{chat}/attachments` (agent, Sanctum)
- `POST /api/v1/widget/upload-attachment` (visitor, site_key + domain checked)

**You need to run once:**

```bash
php artisan storage:link
```

This makes `storage/app/public/chat-attachments/...` reachable at
`/storage/chat-attachments/...` — without it, uploaded files save fine but
their URLs 404.

## 3. Icons

Emoji were replaced with a small inline SVG set:
- Dashboard: `src/components/Icon.vue` — a single component, `<Icon name="inbox" />`.
- Widget: inline `<svg>` per component (kept dependency-free since it ships
  as a single IIFE bundle).

## 4. Compact / SaaS-style layout

Tightened spacing tokens (`theme.css`), button padding, sidebar width, font
sizes across the dashboard. Same components, just less padding and smaller
type — no structural rewrite, so none of your data flow changed.

## 5. Widget live preview

`dashboard/src/components/WidgetPreview.vue` — a sticky panel next to the
"Add/Edit site" form that re-renders as you type (color, greeting, suggested
questions, position, bubble vs full-page). It's a lightweight visual replica
built with plain CSS, not the actual compiled widget bundle, so it doesn't
need CORS or a live API call to preview — trade-off is it can drift slightly
from the real widget's CSS if you restyle `widget/src/components/*.vue`
later without updating this file too.

## Also fixed while in here

- `config/cors.php`: `allowed_origins` opened to `*` (already applied
  earlier for the widget's cross-origin requests; noting it here since it's
  load-bearing for the attachment upload to work from a client's own site).
