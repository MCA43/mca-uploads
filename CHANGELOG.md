# Changelog

## 0.2.0 — 2026-07-26

### Added
- Cloud Box HTTP driver: `HttpCloudBoxClient`, `CloudBoxObjectStore`, `CloudBoxException`
- `UploadManager::storeFor()` returns Cloud Box store when `upload.cloudbox.enabled` is true
- Stored remote paths use opaque keys: `cloudbox:{uuid}`
- Config: `api_prefix`, `timeout`, `folder_id`, `visibility`, `signed_url_minutes`
- Unit tests with `Http::fake` for upload / url / delete / signed URL

### Changed
- `mca:upload:install` skips local directory creation when Cloud Box is enabled
- README Cloud Box section documents env vars and path format

## 0.1.4 — 2026-07-26

### Fixed
- Alpine image `error` handler uses `x-on:error` so Blade no longer treats it as the `@error` validation directive (ParseError)

## 0.1.3 — 2026-07-26

### Fixed
- Preview URLs for `web` / public-root disks are now root-relative (`/uploads/...`) so http↔https host mismatches no longer break `<img>` previews
- Accept hint text (`PNG · JPG · WEBP`) no longer shows broken `/PNG` fragments
- Image tile uses `x-bind:src` + error handler instead of brittle `x-if` template

## 0.1.2 — 2026-07-26

### Fixed
- Dotted preset keys (`branding.favicon`) now resolve correctly (no longer fall back to `uploads/mca`)

### Changed
- Modern dashed tile UI for `image-field` (preview, hover “Değiştir” chip, drag-and-drop)
- Publishes / auto-copies `vendor/mca-upload/mca-upload.css`

## 0.1.1 — 2026-07-26

### Changed
- `image-field`: optional `id`, `preserve`, `currentName` props (settings can own the path hidden field)
- `UploadManager::delete` only removes keys under `uploads/` (static brand assets are never deleted)

## 0.1.0 — 2026-07-25

### Added
- `ObjectStore` contract + `LaravelFilesystemObjectStore`
- `UploadManager` (`store`, `replace`, `delete`, `url`)
- MIME/finfo validation, blocked extensions, image decode check
- Branding presets (favicon, light/dark logos)
- Blade `image-field` component with client preview
- `mca:upload:install` command
- Hub registration when `mca/hub` is present
- Config placeholders for future Cloud Box HTTP driver
