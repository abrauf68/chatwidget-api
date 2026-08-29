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

## 6. Auto-claim on first reply

Replying to an unassigned chat now claims it automatically — no separate
"Claim" click needed. `ChatSessionController::reply` and `::uploadAttachment`
both claim the session first if it's unassigned. `ChatSessionPolicy::reply`
was tightened to match: an agent can reply while a chat is unclaimed or
already theirs, but not to a chat another agent already owns (transfer it
instead). The "Claim" button in the Inbox still exists for reserving a chat
*without* sending a message yet.

## 7. Chat transfer UI

`dashboard/src/components/TransferPicker.vue` — a small popover in the
conversation header (visible once a chat is claimed) listing other agents
with access to that site (`GET /v1/agents?site_id=`), calling the existing
`POST /chats/{chat}/transfer` endpoint. `ChatSessionPolicy::transfer` now
also allows super admins to transfer any chat, not just the assigned agent.

## 8. Agent → site assignment UI

`Agents.vue` now has an "Edit sites" action per agent row that opens an
inline checklist and saves via the existing
`PUT /agents/{agent}/sites` endpoint. Previously site access could only be
set once, at creation.

## 9. Unread badge

- Backend: `indexForSite` now computes a real `unread_count` per session via
  `withCount` (previously it only checked the single latest loaded message,
  which was wrong for anything but a 1-message conversation). Opening a
  chat (`GET /chats/{chat}`) marks its visitor messages read server-side.
- Frontend: `chats.totalUnread` sums unread counts for the active site;
  the Inbox sidebar link shows a red badge with that number. New chats
  from the realtime queue channel start with `unread_count: 1`; opening a
  chat zeroes it locally to match the server.
- This only tracks the currently-open site in the Inbox, not every site at
  once — a true cross-site total would need a dedicated summary endpoint,
  which felt like more than this round needed.

## 10. Settings page + basic account management

`Settings.vue` — update display name/email (`PUT /v1/me`, new
`UpdateProfileRequest`), change password (`PUT /v1/me/password`, new
`UpdatePasswordRequest`, requires the current password), theme toggle, and
a logout button (mobile hides the sidebar's logout button, so it needed a
second home).

## 11. Notifications bell

`dashboard/src/stores/notifications.js` + `NotificationsBell.vue` — on app
load, subscribes to every accessible site's `site.{id}.queue` channel (not
just whichever site is open in the Inbox) plus the agent's own
`agent.{id}.notifications` channel, and shows a bell with an unseen-count
badge in the sidebar. This is in-memory only (resets on refresh) — no
backend persistence was added for it.

## 12. Mobile responsiveness

- Sidebar becomes a bottom icon bar under 760px.
- Inbox becomes two sliding full-width panels (list, then conversation)
  with a back button, instead of a fixed two-pane layout that would be too
  cramped on a phone.
- Sites, Agents, and Settings already used auto-fill grids / stacked cards
  that collapse naturally; `Agents.vue` was also changed from a table to
  card rows, which incidentally made it mobile-friendly too.
- `.page` padding tightens, and the login card caps at `90vw` so it doesn't
  overflow on narrow screens.

## Widget: attachments not showing / no attach button

This wasn't a code bug — the attach button and attachment rendering were
added to `widget/src/components/Composer.vue` and `MessageList.vue` in the
previous round, but the live `widget.js` being served was built *before*
that change. Rebuild and redeploy:

```bash
cd chat-widget
npm run build
# copy dist/widget.js over the one served by the backend (e.g. public/widget.js)
```

Also double-check `php artisan storage:link` has been run (see item 2
above) — without it, attachment URLs resolve but 404.

