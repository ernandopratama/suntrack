<?php

namespace App\Jobs;

use App\Services\Reporting\ReportingService;
use App\Services\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(
        protected string $reportType,
        protected string $format = 'csv',
        protected array $filters = [],
        protected ?string $requestedByEmail = null
    ) {}

    public function handle(ReportingService $reportingService): void
    {
        Log::info(" [Queue:GenerateReportExportJob] Starting background report generation: {$this->reportType} ({$this->format})");

        try {
            $data = $reportingService->generate($this->reportType, $this->filters);
            $filename = "reports/async/suntrack_{$this->reportType}_report_" . date('Ymd_His') . "." . strtolower($this->format);
            
            // For background queue, we store the generated tabular data or formatted stream to storage
            Storage::disk('local')->put($filename, json_encode($data, JSON_PRETTY_PRINT));

            ActivityLogger::log(
                action: 'Report:AsyncGenerated',
                description: "Background report [{$this->reportType}] generated successfully in format [{$this->format}]",
                actorType: 'System',
                actorName: 'QueueWorker',
                properties: [
                    'report_type' => $this->reportType,
                    'format' => $this->format,
                    'file_path' => $filename,
                    'requested_by' => $this->requestedByEmail,
                ]
            );

            Log::info(" [Queue:GenerateReportExportJob] Report saved successfully: {$filename}");
        } catch (\Exception $e) {
            Log::error(" [Queue:GenerateReportExportJob] Failed: {$e->getMessage()}");
            throw $e;
        }
    }
}
