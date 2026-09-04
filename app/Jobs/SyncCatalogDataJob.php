<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Variant;
use App\Services\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCatalogDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        Log::info(' [Queue:SyncCatalogDataJob] Starting background master catalog sync...');

        $productCount = Product::count();
        $variantCount = Variant::count();

        ActivityLogger::log(
            action: 'Catalog:SyncCompleted',
            description: "Background master catalog synchronization completed ({$productCount} products, {$variantCount} variants verified)",
            actorType: 'System',
            actorName: 'QueueWorker',
            properties: [
                'total_products' => $productCount,
                'total_variants' => $variantCount,
                'synced_at' => now()->toIso8601String(),
            ]
        );

        Log::info(' [Queue:SyncCatalogDataJob] Catalog sync complete.');
    }
}
