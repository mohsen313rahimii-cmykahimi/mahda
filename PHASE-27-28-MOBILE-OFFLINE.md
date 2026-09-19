# Mahda Phase 27–28 — Mobile Auth + Offline Draft Sync

This release intentionally advances only the next two steps.

## Phase 27 — Native app Access/Refresh authentication

The existing browser session/CSRF authentication is preserved. Native clients now have a separate API contract that does not depend on cookies.

### New endpoints

```text
POST /api/v1/mobile/auth/login
POST /api/v1/mobile/auth/register
POST /api/v1/mobile/auth/refresh
POST /api/v1/mobile/auth/logout
GET  /api/v1/mobile/me
```

### Token model

- Access tokens are opaque random tokens, default lifetime 15 minutes.
- Refresh tokens are opaque random tokens, default lifetime 30 days.
- Only SHA-256 token hashes are stored in MariaDB; plaintext tokens are returned once to the client.
- Refresh tokens rotate on every successful refresh.
- Reuse of an already-rotated refresh token revokes the whole token family for that device session.
- Logout revokes the token family.
- Mobile auth requires HTTPS by default. For local-only development, `MAHDA_ALLOW_INSECURE_MOBILE_AUTH=1` can temporarily disable this guard.
- Login/register/refresh use the existing rate limiter.

The mobile app must store the refresh token in OS secure storage (Android Keystore / iOS Keychain equivalent), never in ordinary preferences or SQLite plaintext.

### Migration 27

Adds `mahda_mobile_tokens` with access/refresh token hashes, token family, expiry and revocation information.

## Phase 28 — Conflict-safe offline draft sync foundation

The existing content draft `version` is now the source of truth for optimistic concurrency.

### New endpoints

```text
POST /api/v1/sync/drafts
GET  /api/v1/sync/drafts?cursor=0&limit=50
```

Both endpoints require a valid mobile Bearer access token and a completed user profile.

### Push contract

A client sends up to 25 changes:

```json
{
  "changes": [
    {
      "clientKey": "device-generated-stable-key",
      "baseVersion": 3,
      "step": 4,
      "data": {}
    }
  ]
}
```

Rules:

- A new offline draft uses `baseVersion: 0`.
- Updating an existing draft requires the exact server version last seen by the device.
- If versions differ, the server returns a `conflict` result and the current server draft instead of overwriting data.
- Blob URLs and base64 file payloads are rejected from draft JSON; media remains a separate upload concern.
- The server reuses the same draft sanitizer as the web registration flow.

### Pull contract

The app keeps the integer `nextCursor` returned by the server and asks for changes after it. A dedicated `mahda_sync_changes` append-only change log prevents timestamp race conditions.

Web draft creation/saves, media upload/delete, media worker completion/failure, publishing, and mobile draft saves record sync changes. Migration 28 backfills existing drafts into the change log so the first mobile sync can discover drafts that existed before this release.

### Important boundary for this phase

This phase syncs draft metadata/text and server-side media status. It does **not** yet implement native resumable binary upload. Offline-selected image/video files should stay in the app's local queue until the later mobile upload phase is implemented.
Draft sync returns file IDs and processing metadata but intentionally does not expose the legacy cookie-authenticated private file URL as a mobile download contract.

## Deploy order

1. Back up database and current release.
2. Deploy code.
3. Run `php tools/migrate.php` once from CLI.
4. Run `php tools/verify-release.php`.
5. Run `php tools/verify-phase27-28.php`.
6. Test login → `/mobile/me` → refresh → logout on HTTPS.
7. Test draft push/pull with two different simulated device versions and verify conflict response.

Do not enable an app release against these endpoints before migration 27 and 28 have completed.

## Not done in this release

To keep the requested two-step pace, this release does not add resumable mobile media upload, Push Notifications, backup automation, AI search, monitoring, or admin redesign.
