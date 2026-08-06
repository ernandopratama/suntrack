<?php

namespace App\Services\Storage\Drivers;

use App\Contracts\Storage\StorageDriverInterface;
use Illuminate\Support\Facades\Storage;

class LocalDriver implements StorageDriverInterface
{
    protected string $disk;

    public function __construct(string $disk = 'public')
    {
        $this->disk = $disk;
    }

    public function put(string $path, $content, array $options = []): bool|string
    {
        return Storage::disk($this->disk)->put($path, $content, $options);
    }

    public function get(string $path): ?string
    {
        return $this->exists($path) ? Storage::disk($this->disk)->get($path) : null;
    }

    public function url(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function getDriverName(): string
    {
        return 'local';
    }
}
