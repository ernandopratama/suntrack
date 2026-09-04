<?php

namespace App\Contracts\Storage;

interface StorageDriverInterface
{
    public function put(string $path, $content, array $options = []): bool|string;

    public function get(string $path): ?string;

    public function url(string $path): string;

    public function delete(string $path): bool;

    public function exists(string $path): bool;

    public function getDriverName(): string;
}
