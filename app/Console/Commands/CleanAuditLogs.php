<?php

namespace App\Console\Commands;

use App\Services\AuditService;
use Illuminate\Console\Command;

class CleanAuditLogs extends Command
{
    protected $signature = 'audit:clean';
    protected $description = 'Clean old audit logs based on retention policy';

    public function handle(AuditService $auditService)
    {
        $this->info('Cleaning old audit logs...');

        $deleted = $auditService->cleanOldLogs();

        $this->info("Deleted {$deleted} old audit logs");

        return 0;
    }
}
