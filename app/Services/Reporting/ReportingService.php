<?php

namespace App\Services\Reporting;

use App\Contracts\Reporting\ReportExporterInterface;
use App\Contracts\Reporting\ReportGeneratorInterface;
use App\Models\ActivityLog;
use App\Models\ApprovalHistory;
use App\Models\Campaign;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Variant;
use App\Services\Authorization\DataScopeService;
use App\Services\Reporting\Adapters\CsvExporter;
use App\Services\Reporting\Adapters\ExcelExporter;
use App\Services\Reporting\Adapters\PdfExporter;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ReportingService implements ReportGeneratorInterface
{
    /** @var array<string, ReportExporterInterface> */
    protected array $exporters = [];

    public function __construct(
        protected DataScopeService $dataScope = new DataScopeService
    ) {
        // Register format adapters (Sprint 7 & 8)
        $this->registerExporter(new CsvExporter);
        $this->registerExporter(new ExcelExporter);
        $this->registerExporter(new PdfExporter);
    }

    /**
     * Register a new export adapter (e.g., Excel, PDF, Google Sheets).
     */
    public function registerExporter(ReportExporterInterface $exporter): void
    {
        $this->exporters[strtolower($exporter->getSupportedFormat())] = $exporter;
    }

    /**
     * Generate structured tabular dataset for a specific report domain.
     */
    public function generate(string $reportType, array $filters = [], ?User $user = null): array
    {
        return match (strtolower(trim($reportType))) {
            'campaign', 'campaigns' => $this->generateCampaignReport($filters, $user),
            'promotion', 'promotions' => $this->generatePromotionReport($filters, $user),
            'approval', 'approvals' => $this->generateApprovalReport($filters, $user),
            'product', 'products', 'variant', 'variants' => $this->generateProductReport($filters, $user),
            'activity', 'activities', 'logs' => $this->generateActivityReport($filters, $user),
            default => throw new InvalidArgumentException("Unsupported report type: {$reportType}"),
        };
    }

    /**
     * Export a generated report using the requested format driver.
     */
    public function export(string $reportType, string $format = 'csv', array $filters = [], ?string $filename = null, ?User $user = null): mixed
    {
        $format = strtolower($format);
        if (! isset($this->exporters[$format])) {
            throw new InvalidArgumentException("No export adapter registered for format: {$format}");
        }

        $data = $this->generate($reportType, $filters, $user);
        $filename = $filename ?: "suntrack_{$reportType}_report_".date('Ymd_His').".{$format}";

        return $this->exporters[$format]->export($data, $format, $filename);
    }

    public function getAvailableReportTypes(): array
    {
        return [
            'campaign' => 'Campaign Summary Report',
            'promotion' => 'Promotion & Pricing Report',
            'approval' => 'Brand Approval Audit Report',
            'product' => 'Master Catalog & Margin Floor Report',
            'activity' => 'System & Brand Activity Log Report',
        ];
    }

    protected function generateCampaignReport(array $filters, ?User $user = null): array
    {
        $query = $this->scoped(Campaign::with(['brand', 'pic']), $user)->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        return $query->get()->map(function ($c) {
            return [
                'ID' => $c->id,
                'Campaign Name' => $c->name,
                'Brand' => $c->brand->name ?? 'All Brands / Standalone',
                'Start Date' => $c->start_date?->format('Y-m-d') ?? '-',
                'End Date' => $c->end_date?->format('Y-m-d') ?? '-',
                'Status' => $c->status ?? '-',
                'PIC Name' => $c->pic->name ?? '-',
                'Deadline' => $c->deadline?->format('Y-m-d H:i') ?? '-',
                'Created At' => $c->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function generatePromotionReport(array $filters, ?User $user = null): array
    {
        $query = $this->scoped(Promotion::with(['campaign', 'brand', 'variants']), $user)->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        return $query->get()->map(function ($p) {
            $totalEstValue = $p->variants->sum(fn ($v) => ($v->pivot->campaign_price ?? 0) * ($v->pivot->promotion_stock ?? 0));

            return [
                'ID' => $p->id,
                'Promotion Code' => $p->code ?? '-',
                'Promotion Name' => $p->name,
                'Campaign Name' => $p->campaign->name ?? 'Standalone',
                'Brand' => $p->brand->name ?? '-',
                'Status' => $p->status ?? '-',
                'Start Date' => $p->start_date?->format('Y-m-d') ?? '-',
                'End Date' => $p->end_date?->format('Y-m-d') ?? '-',
                'Total Variants' => $p->variants->count(),
                'Total Promo Stock' => $p->variants->sum('pivot.promotion_stock'),
                'Est. Value (IDR)' => $totalEstValue,
                'Created At' => $p->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function generateApprovalReport(array $filters, ?User $user = null): array
    {
        $query = $this->scoped(ApprovalHistory::with(['promotion', 'variant']), $user)->orderBy('created_at', 'desc');

        if (! empty($filters['promotion_id'])) {
            $query->where('promotion_id', $filters['promotion_id']);
        }

        return $query->get()->map(function ($h) {
            return [
                'ID' => $h->id,
                'Promotion Code' => $h->promotion->code ?? '-',
                'Promotion Name' => $h->promotion->name ?? '-',
                'Variant SKU' => $h->variant->sku ?? '-',
                'Variant Name' => $h->variant->name ?? '-',
                'Old Status' => $h->old_status ?? '-',
                'New Status' => $h->new_status ?? '-',
                'Reviewer Name' => $h->reviewer_name ?? '-',
                'Reviewer Position' => $h->reviewer_position ?? '-',
                'Company Name' => $h->company_name ?? '-',
                'WhatsApp Number' => $h->whatsapp_number ?? '-',
                'Rejection Notes' => $h->notes ?? '-',
                'Decision Date' => $h->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function generateProductReport(array $filters, ?User $user = null): array
    {
        $query = $this->scoped(Variant::with(['product.brand']), $user)->orderBy('sku', 'asc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get()->map(function ($v) {
            return [
                'Variant SKU' => $v->sku,
                'Variant Name' => $v->name,
                'Product Name' => $v->product->name ?? '-',
                'Brand' => $v->product->brand->name ?? '-',
                'Normal Price (IDR)' => $v->normal_price,
                'Bottom Price (IDR)' => $v->bottom_price,
                'Current Stock' => $v->current_stock,
                'Status' => $v->status ?? 'Active',
                'Last Updated' => $v->updated_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function generateActivityReport(array $filters, ?User $user = null): array
    {
        $query = $this->scoped(ActivityLog::with(['actor', 'loggable']), $user)->orderBy('created_at', 'desc')->limit(500);

        if (! empty($filters['actor_type'])) {
            $query->where('actor_type', $filters['actor_type']);
        }

        return $query->get()->map(function ($log) {
            return [
                'ID' => $log->id,
                'Action' => $log->action,
                'Description' => $log->description,
                'Actor Type' => $log->actor_type,
                'Actor Name' => $log->actor_name ?? '-',
                'Target Entity' => class_basename($log->loggable_type ?? ''),
                'Target ID' => $log->loggable_id ?? '-',
                'IP Address' => $log->ip_address ?? '-',
                'User Agent' => substr($log->user_agent ?? '-', 0, 50),
                'Timestamp' => $log->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function scoped(Builder $query, ?User $user): Builder
    {
        return $user === null ? $query : $this->dataScope->scope($query, $user);
    }
}
