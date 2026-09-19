# Mahda Phase 25–26 — Storage + Redis foundation

This release intentionally advances only two infrastructure steps.

## Phase 25 — Object Storage ready

- `local` remains the default storage driver, so the current Netafraz deployment keeps working without S3 credentials.
- Added a private S3-compatible storage driver using AWS Signature V4 and cURL; no Composer SDK is required.
- Media uploads are staged locally for GD/FFmpeg, then persisted through the storage adapter.
- The S3 driver downloads remote objects to a private local cache when legacy PHP/FFmpeg needs a filesystem path.
- Content deletion and media replacement use the storage adapter instead of assuming a local disk.
- Private config may override `storage` settings so secrets do not need to live in the repository.

### S3 environment variables

```text
MAHDA_STORAGE_DRIVER=s3
MAHDA_S3_ENDPOINT=https://...
MAHDA_S3_BUCKET=...
MAHDA_S3_REGION=...
MAHDA_S3_ACCESS_KEY=...
MAHDA_S3_SECRET_KEY=...
MAHDA_S3_PATH_STYLE=1
```

Do not enable `s3` until the bucket and credentials are tested. A configured-but-broken S3 target fails closed instead of silently splitting new files between local and remote storage.

## Phase 26 — Redis ready

- Redis is optional and disabled by default.
- When enabled and ext-redis is available, web sessions use Redis with a short distributed lock to reduce concurrent session-write races.
- API rate limits use Redis atomic counters first, then safely fall back to the existing database limiter if Redis is unavailable.
- Added a small Redis-backed cache facade for later modules.
- Redis settings can also be supplied through the private config under a `redis` key.

### Redis environment variables

```text
MAHDA_REDIS_ENABLED=1
MAHDA_REDIS_SCHEME=tcp
MAHDA_REDIS_HOST=127.0.0.1
MAHDA_REDIS_PORT=6379
MAHDA_REDIS_USERNAME=
MAHDA_REDIS_PASSWORD=
MAHDA_REDIS_DATABASE=0
MAHDA_REDIS_PREFIX=mahda:
MAHDA_REDIS_TIMEOUT=1.5
MAHDA_SESSION_TTL_SECONDS=43200
```

If Redis is disabled, missing, or temporarily unreachable, PHP native sessions and the database rate limiter continue to work.

## Validation

- All PHP files lint successfully.
- `php tools/verify-release.php` passes.
- Local storage put/read/delete fallback was functionally checked.
- Redis-disabled fallback was functionally checked.

## Not done in this release

To keep the requested two-step pace, this release does **not** add mobile tokens, offline sync, push notifications, backup automation, AI search, monitoring, or admin redesign.
