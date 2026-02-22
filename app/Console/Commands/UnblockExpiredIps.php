<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UnblockExpiredIps extends Command
{
    protected $signature = 'security:unblock-expired';
    protected $description = 'Remove expired IP blocks';

    public function handle()
    {
        $this->info('Removing expired IP blocks...');

        $deleted = DB::table('ip_blocks')
            ->where('expires_at', '<=', now())
            ->whereNotNull('expires_at')
            ->delete();

        $this->info("Unblocked {$deleted} IP addresses");

        return 0;
    }
}
