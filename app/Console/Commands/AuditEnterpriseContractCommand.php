<?php

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditEnterpriseContractCommand extends Command
{
    protected $signature = 'suntrack:contract-audit
        {--observation-start= : Awal observasi produksi ISO-8601}
        {--observation-end= : Akhir observasi produksi ISO-8601}
        {--backup-reference= : Lokasi atau ID backup database tervalidasi}
        {--json : Keluarkan hasil sebagai JSON}';

    protected $description = 'Audit kesiapan kontraksi schema enterprise tanpa mengubah data';

    public function handle(): int
    {
        $checks = [
            'fase_e_schema' => $this->hasPhaseESchema(),
            'campaign_created_by_complete' => ! DB::table('campaigns')->whereNull('created_by')->exists(),
            'task_brand_complete' => ! DB::table('tasks')->whereNull('brand_id')->exists(),
            'task_created_by_complete' => ! DB::table('tasks')->whereNull('created_by')->exists(),
            'campaign_status_canonical' => ! DB::table('campaigns')->whereNotIn('status', array_column(CampaignStatus::cases(), 'value'))->exists(),
            'task_status_canonical' => ! DB::table('tasks')->whereNotIn('progress_status', array_column(TaskStatus::cases(), 'value'))->exists(),
            'production_observation_complete' => $this->validObservationWindow(),
            'verified_backup_supplied' => filled($this->option('backup-reference')),
        ];
        $ready = ! in_array(false, $checks, true);
        $payload = [
            'ready_for_contract' => $ready,
            'checks' => $checks,
            'legacy_snapshot_rows' => Schema::hasTable('rbac_legacy_user_snapshots')
                ? DB::table('rbac_legacy_user_snapshots')->count()
                : 0,
            'backup_reference' => $this->option('backup-reference'),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Check', 'Status'], collect($checks)->map(
                fn (bool $status, string $name) => [$name, $status ? 'PASS' : 'BLOCKED']
            )->values()->all());
            $this->line($ready ? 'CONTRACT_READY' : 'CONTRACT_BLOCKED');
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    private function hasPhaseESchema(): bool
    {
        return Schema::hasTable('attachments')
            && Schema::hasTable('comment_reads')
            && Schema::hasTable('secure_link_access_logs')
            && Schema::hasColumns('tasks', ['next_reminder_at', 'last_reminded_at', 'reminder_count']);
    }

    private function validObservationWindow(): bool
    {
        try {
            $start = CarbonImmutable::parse((string) $this->option('observation-start'));
            $end = CarbonImmutable::parse((string) $this->option('observation-end'));

            return $end->greaterThan($start)
                && $start->diffInDays($end) >= 7
                && $end->lessThanOrEqualTo(now());
        } catch (\Throwable) {
            return false;
        }
    }
}
