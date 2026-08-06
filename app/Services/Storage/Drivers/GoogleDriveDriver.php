<?php

namespace App\Services\Storage\Drivers;

use App\Contracts\Storage\StorageDriverInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GoogleDriveDriver implements StorageDriverInterface
{
    protected string $disk = 'google_drive';

    public function put(string $path, $content, array $options = []): bool|string
    {
        try {
            return Storage::disk($this->disk)->put($path, $content, $options);
        } catch (\Exception $e) {
            Log::error('GoogleDriveDriver put error: ' . $e->getMessage());
            // Fallback to local disk if Google Drive disk is unconfigured in local dev
            return Storage::disk('local')->put('gdrive-fallback/' . $path, $content, $options);
        }
    }

    public function get(string $path): ?string
    {
        try {
            return $this->exists($path) ? Storage::disk($this->disk)->get($path) : null;
        } catch (\Exception $e) {
            return Storage::disk('local')->get('gdrive-fallback/' . $path);
        }
    }

    public function url(string $path): string
    {
        try {
            return Storage::disk($this->disk)->url($path);
        } catch (\Exception $e) {
            return '/storage/gdrive-fallback/' . $path;
        }
    }

    public function delete(string $path): bool
    {
        try {
            return Storage::disk($this->disk)->delete($path);
        } catch (\Exception $e) {
            return Storage::disk('local')->delete('gdrive-fallback/' . $path);
        }
    }

    public function exists(string $path): bool
    {
        try {
            return Storage::disk($this->disk)->exists($path);
        } catch (\Exception $e) {
            return Storage::disk('local')->exists('gdrive-fallback/' . $path);
        }
    }

    public function getDriverName(): string
    {
        return 'google_drive';
    }
}
