<?php

namespace App\Services\Storage;

use App\Contracts\Storage\StorageDriverInterface;
use App\Services\Storage\Drivers\LocalDriver;
use App\Services\Storage\Drivers\S3Driver;
use App\Services\Storage\Drivers\GoogleDriveDriver;
use App\Services\Settings\SettingsService;
use InvalidArgumentException;

class StorageService implements StorageDriverInterface
{
    protected array $drivers = [];
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
        $this->registerDriver('local', new LocalDriver('public'));
        $this->registerDriver('s3', new S3Driver());
        $this->registerDriver('google_drive', new GoogleDriveDriver());
    }

    public function registerDriver(string $name, StorageDriverInterface $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    public function driver(?string $name = null): StorageDriverInterface
    {
        $name = $name ?: $this->getDefaultDriverName();
        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Storage driver [{$name}] is not registered.");
        }
        return $this->drivers[$name];
    }

    public function getDefaultDriverName(): string
    {
        return $this->settingsService->get('storage.default_disk', env('FILESYSTEM_DISK', 'local'));
    }

    public function put(string $path, $content, array $options = []): bool|string
    {
        return $this->driver()->put($path, $content, $options);
    }

    public function get(string $path): ?string
    {
        return $this->driver()->get($path);
    }

    public function url(string $path): string
    {
        return $this->driver()->url($path);
    }

    public function delete(string $path): bool
    {
        return $this->driver()->delete($path);
    }

    public function exists(string $path): bool
    {
        return $this->driver()->exists($path);
    }

    public function getDriverName(): string
    {
        return $this->driver()->getDriverName();
    }
}
