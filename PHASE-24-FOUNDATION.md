# Mahda Foundation Phase 24

This release keeps the existing PHP modular monolith and adds the first production-scale foundations without breaking the legacy API.

## Completed in this phase

1. **Deploy-time migrations** — normal API requests no longer run all schema migrations. Run `php tools/migrate.php` during deploy. A temporary `MAHDA_ALLOW_REQUEST_MIGRATIONS=1` escape hatch exists only for legacy transition.
2. **Versioned API entrypoint** — `/api/v1/*` now exists beside the legacy `/mahda-api/index.php?action=...` API.
3. **Storage abstraction** — media paths now go through `Mahda\Core\Storage\StorageInterface` / `LocalPrivateStorage`, leaving a clean seam for S3-compatible object storage later.
4. **Queued media jobs** — media jobs are stored in `mahda_media_jobs`; request-time worker spawning is disabled by default. `MAHDA_SPAWN_MEDIA_WORKER=1` is an explicit compatibility option only.
5. **Image target** — accepted JPEG/PNG/WebP images are resized/re-encoded until the final stored image is at most ~200 KiB. If the server cannot achieve the target, the upload is rejected rather than silently storing an oversized image.
6. **Video policy** — video processing requires ffmpeg + ffprobe, rejects duration above 300 seconds, transcodes to H.264/AAC with adaptive bitrate/resolution, and verifies final output is at most 20 MiB. A second lower-bitrate pass is attempted if necessary.
7. **Rate limiting foundation** — a replaceable fixed-window limiter protects authentication and upload paths. Migration v24 creates `mahda_rate_limits`; Redis can replace this adapter later.
8. **Central media configuration** — `config/media.php` owns image/video policy and environment overrides.
9. **Release verification** — all PHP files lint cleanly and the existing release verification passes.

## Production deployment sequence

```bash
php tools/migrate.php
php tools/verify-release.php
```

A worker process/cron must consume pending media jobs. The current worker processes one pending item when no file id is passed:

```bash
php mahda-api/media-worker.php
```

For a proper VPS deployment, run workers under systemd/supervisor rather than spawning them from web requests.

## Media defaults

- final image target: 200 KiB
- temporary video input cap: 150 MiB (also bounded by PHP upload/post limits)
- final video cap: 20 MiB
- maximum video duration: 300 seconds
- maximum video output height: 540p by default
- video audio bitrate: 64 kbps

Environment overrides are documented directly in `config/media.php`.

## Important next steps

- Object storage + signed URLs + CDN
- Redis session/cache/rate-limit adapter
- token-based mobile authentication
- offline sync API and local draft protocol
- push notification worker
- cursor pagination and search indexing
- backup/restore automation and monitoring

The admin redesign is intentionally not included in this phase.
