<?php

namespace App\Services\Storage\Drivers;

use App\Contracts\Storage\StorageDriverInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class S3Driver implements StorageDriverInterface
{
    protected string $disk = 's3';

    public function put(string $path, $content, array $options = []): bool|string
    {
        try {
            return Storage::disk($this->disk)->put($path, $content, $options);
        } catch (\Exception $e) {
            Log::error('S3Driver put error: '.$e->getMessage());

            return false;
        }
    }

    public function get(string $path): ?string
    {
        try {
            return $this->exists($path) ? Storage::disk($this->disk)->get($path) : null;
        } catch (\Exception $e) {
            Log::error('S3Driver get error: '.$e->getMessage());

            return null;
        }
    }

    public function url(string $path): string
    {
        try {
            return Storage::disk($this->disk)->url($path);
        } catch (\Exception $e) {
            return '/storage/error-s3-unconfigured/'.$path;
        }
    }

    public function delete(string $path): bool
    {
        try {
            return Storage::disk($this->disk)->delete($path);
        } catch (\Exception $e) {
            Log::error('S3Driver delete error: '.$e->getMessage());

            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            return Storage::disk($this->disk)->exists($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getDriverName(): string
    {
        return 's3';
    }
}
