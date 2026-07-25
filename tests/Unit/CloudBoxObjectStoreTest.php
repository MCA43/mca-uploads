<?php

namespace Mca\Upload\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Mca\Upload\Services\CloudBoxObjectStore;
use Mca\Upload\Services\HttpCloudBoxClient;
use Mca\Upload\Services\UploadManager;
use Mca\Upload\UploadServiceProvider;
use Orchestra\Testbench\TestCase;

class CloudBoxObjectStoreTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [UploadServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('upload.cloudbox', [
            'enabled' => true,
            'base_url' => 'https://cloudbox.test',
            'token' => 'test-token',
            'api_prefix' => 'api/v1',
            'timeout' => 10,
            'folder_id' => null,
            'visibility' => 'public',
            'signed_url_minutes' => 30,
        ]);

        $app['config']->set('upload.disk', 'public');
        $app['config']->set('upload.directory', 'uploads/mca');
        $app['config']->set('upload.max_kb', 2048);
        $app['config']->set('upload.allowed_mimes', ['image/png', 'image/jpeg']);
        $app['config']->set('upload.blocked_extensions', ['php']);
        $app['config']->set('upload.allow_svg', false);
        $app['config']->set('upload.name_strategy', 'uniqid');
        $app['config']->set('upload.presets', []);

        $app['config']->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => '/storage',
            'visibility' => 'public',
        ]);
    }

    public function test_store_for_returns_cloudbox_when_enabled(): void
    {
        $store = app(UploadManager::class)->storeFor('public');

        $this->assertInstanceOf(CloudBoxObjectStore::class, $store);
        $this->assertSame('cloudbox', $store->diskName());
    }

    public function test_put_stream_stores_cloudbox_key_and_url(): void
    {
        $uuid = '11111111-2222-3333-4444-555555555555';

        Http::fake([
            'cloudbox.test/api/v1/files' => Http::response([
                'data' => [
                    'uuid' => $uuid,
                    'name' => 'logo.png',
                    'public_url' => 'https://cloudbox.test/2026/07/logo.png',
                    'visibility' => 'public',
                ],
            ], 201),
            "cloudbox.test/api/v1/files/{$uuid}" => Http::response([
                'data' => [
                    'uuid' => $uuid,
                    'name' => 'logo.png',
                    'public_url' => 'https://cloudbox.test/2026/07/logo.png',
                    'visibility' => 'public',
                ],
            ], 200),
        ]);

        $store = app(CloudBoxObjectStore::class);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'png-bytes');
        rewind($stream);

        $store->putStream('uploads/branding/logo.png', $stream, ['filename' => 'logo.png']);
        fclose($stream);

        $key = $store->consumeLastStoredKey();
        $this->assertSame('cloudbox:'.$uuid, $key);
        $this->assertTrue($store->exists($key));
        $this->assertSame('https://cloudbox.test/2026/07/logo.png', $store->url($key));
    }

    public function test_upload_manager_store_returns_cloudbox_path(): void
    {
        $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

        Http::fake([
            'cloudbox.test/api/v1/files' => Http::response([
                'data' => [
                    'uuid' => $uuid,
                    'name' => 'upload.png',
                    'public_url' => 'https://cdn.example/upload.png',
                    'visibility' => 'public',
                ],
            ], 201),
            "cloudbox.test/api/v1/files/{$uuid}" => Http::response([
                'data' => [
                    'uuid' => $uuid,
                    'public_url' => 'https://cdn.example/upload.png',
                ],
            ], 200),
        ]);

        $file = UploadedFile::fake()->image('badge.png', 20, 20);

        $stored = app(UploadManager::class)->store($file);

        $this->assertSame('cloudbox:'.$uuid, $stored->path);
        $this->assertSame('cloudbox', $stored->disk);
        $this->assertSame('https://cdn.example/upload.png', $stored->url);
    }

    public function test_delete_and_url_helpers_accept_cloudbox_keys(): void
    {
        $uuid = '99999999-8888-7777-6666-555555555555';

        Http::fake([
            "cloudbox.test/api/v1/files/{$uuid}" => Http::sequence()
                ->push([
                    'data' => [
                        'uuid' => $uuid,
                        'public_url' => 'https://cdn.example/x.png',
                    ],
                ], 200)
                ->push(null, 204),
            "cloudbox.test/api/v1/files/{$uuid}*" => Http::response(null, 204),
        ]);

        $manager = app(UploadManager::class);
        $path = 'cloudbox:'.$uuid;

        $this->assertSame('https://cdn.example/x.png', $manager->url($path));
        $manager->delete($path);

        Http::assertSent(function ($request) use ($uuid) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), '/files/'.$uuid);
        });
    }

    public function test_signed_url_used_when_public_url_missing(): void
    {
        $uuid = '12121212-3434-5656-7878-909090909090';

        Http::fake([
            "cloudbox.test/api/v1/files/{$uuid}" => Http::response([
                'data' => [
                    'uuid' => $uuid,
                    'public_url' => null,
                    'visibility' => 'private',
                ],
            ], 200),
            "cloudbox.test/api/v1/files/{$uuid}/signed-url" => Http::response([
                'data' => [
                    'url' => 'https://cloudbox.test/s/files/'.$uuid.'?signature=abc',
                    'ttl_minutes' => 30,
                ],
            ], 200),
        ]);

        $client = new HttpCloudBoxClient('https://cloudbox.test', 'test-token');
        $store = new CloudBoxObjectStore($client);

        $this->assertSame(
            'https://cloudbox.test/s/files/'.$uuid.'?signature=abc',
            $store->url('cloudbox:'.$uuid),
        );
    }
}
