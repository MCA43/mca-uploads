# Changelog

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
