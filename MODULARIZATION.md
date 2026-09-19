# Mahda modular PHP release

Build: `20260916-php-modular-v3`  
API: `48`

## What changed

The public application now uses one PHP front controller and feature modules for account/profile, content bank/search/detail/creation, library/social notifications, ContentChin, atlas, showcase, portal pages, media, system health, admin, and studio.

The former large API entrypoint has been split into capability dispatchers under `app/Modules/*/api.php` (plus the content workflow adapter). `mahda-api/index.php` is now only a compatibility entrypoint into `app/Api/Kernel.php`, so existing client URLs stay valid while implementation ownership is modular.

Public duplicate HTML pages were removed. Parameterized routes are resolved by `Mahda\Core\Router`, including content details, showcase profiles, and ContentChin plan views. No legacy SPA entrypoint or generated bundle directory is retained.

## Completed feature areas

- Sign-in/registration and profile completion
- Content bank, search and content detail
- Seven-step content registration and draft persistence
- File/media upload, private storage, streaming and processing hooks
- Library, likes, comments, follows and notifications
- ContentChin: draft creation, bank search, add/remove, ordering, per-item notes, publish/reopen
- Atlas and Iranian city/province data
- Profile/showcase
- Admin/studio compatibility entrypoints with implementations under modules
- Final route/static-page cleanup and modular API kernel

## Verification

Run `php tools/verify-release.php` from the project root. The release was also checked with PHP lint for all PHP files and `node --check` for all JavaScript files.


## Hardening fixes in v3

- Test phone authentication now fails closed unless a valid server-side code is configured, and the code is required on login.
- Private configuration lookup is consistent between the modular core and the legacy compatibility runtime, with environment overrides supported.
- Admin recovery no longer contains a source-code recovery key; it requires a strong server-side secret and stores its one-time lock in private storage.
- Alternate profile creation is transactional, hidden comments no longer inflate public counts, and stale session identities are revalidated before read-state responses.
- Family and cultural-servant profile roles are preserved correctly by the account editor.
