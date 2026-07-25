# mca/uploads

**Türkçe** | [English](README.md#english)

Laravel 13 için güvenli dosya yükleme çekirdeği: disk politikaları, MIME doğrulama, replace/delete yaşam döngüsü, branding preset’leri ve opsiyonel Cloud Box entegrasyon sınırı.

## Özellikler

- **ObjectStore** sözleşmesi — local / public / web / S3 uyumlu Flysystem sarmalayıcı
- **UploadManager** — `store`, `replace`, `delete`, `url`
- **Güvenlik** — finfo MIME, görsel decode kontrolü, çift uzantı engeli, SVG varsayılan kapalı
- **Branding preset’leri** — favicon / light-dark logo
- **Blade widget** — `<x-mca-upload::image-field />` önizlemeli
- **Hub** — `mca/hub` varsa otomatik kayıt

## Kurulum

```bash
composer require mca/uploads
php artisan mca:upload:install
```

`config/filesystems.php` içine paylaşımlı hosting için önerilen `web` diski:

```php
'web' => [
    'driver' => 'local',
    'root' => public_path(),
    'url' => env('APP_URL'),
    'visibility' => 'public',
    'throw' => false,
],
```

## Kullanım

```php
$stored = mca_upload()->store($request->file('logo'), 'branding.dark_logo');

// Eski dosyayı silerek değiştir
$stored = mca_upload()->replace(
    $request->file('logo'),
    mca_setting('branding.dark_logo'),
    'branding.dark_logo',
);

mca_settings()->set('branding.dark_logo', $stored->path);

$url = mca_upload_url($stored->path);
```

Blade:

```blade
<x-mca-upload::image-field
    name="uploads[branding.dark_logo]"
    label="Koyu logo"
    :value="mca_setting('branding.dark_logo')"
    preset="branding.dark_logo"
/>
```

Form `enctype="multipart/form-data"` olmalıdır.

## Cloud Box

v0.1 yalnızca Laravel Filesystem `ObjectStore` sağlar. Uzak [mca-cloud-box](https://github.com/MCA43) HTTP driver Faz 2’de eklenecek (`upload.cloudbox.*` config anahtarları şimdiden ayrıldı).

## Publish

| Tag | Çıktı |
|-----|--------|
| `mca-upload-config` | `config/upload.php` |
| `mca-upload-views` | `resources/views/vendor/mca-upload` |

## GitHub

Repository: [github.com/MCA43/mca-uploads](https://github.com/MCA43/mca-uploads)

---

<a id="english"></a>

## English

Secure upload core for Laravel 13: disk policies, MIME validation, replace/delete lifecycle, branding presets.

```bash
composer require mca/uploads
php artisan mca:upload:install
```

```php
$stored = mca_upload()->store($request->file('logo'), 'branding.dark_logo');
```

Cloud Box HTTP driver is planned for a later release.
