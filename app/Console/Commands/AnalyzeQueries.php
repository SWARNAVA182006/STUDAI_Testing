<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeQueries extends Command
{
    protected $signature = 'db:analyze-queries';
    protected $description = 'Analyze slow queries and provide optimization suggestions';

    public function handle()
    {
        $this->info('Analyzing database queries...');

        // Enable slow query log (if not already enabled)
        $this->info("\nChecking slow query log status...");
        $slowLogStatus = DB::selectOne("SHOW VARIABLES LIKE 'slow_query_log'");
        $this->info("Slow query log: " . $slowLogStatus->Value);

        // Get slow queries from performance schema (MySQL 5.6+)
        $this->info("\nTop 10 Slowest Queries:");
        
        try {
            $slowQueries = DB::select("
                SELECT 
                    DIGEST_TEXT as query,
                    COUNT_STAR as executions,
                    AVG_TIMER_WAIT / 1000000000000 as avg_time_sec,
                    MAX_TIMER_WAIT / 1000000000000 as max_time_sec,
                    SUM_ROWS_EXAMINED as total_rows_examined,
                    SUM_ROWS_SENT as total_rows_sent
                FROM performance_schema.events_statements_summary_by_digest
                WHERE SCHEMA_NAME = ?
                ORDER BY AVG_TIMER_WAIT DESC
                LIMIT 10
            ", [env('DB_DATABASE')]);

            if (empty($slowQueries)) {
                $this->warn('No query data available. Performance schema might be disabled.');
            } else {
                $this->table(
                    ['Query', 'Executions', 'Avg Time (s)', 'Max Time (s)', 'Rows Examined'],
                    array_map(fn($q) => [
                        substr($q->query, 0, 80) . '...',
                        $q->executions,
                        round($q->avg_time_sec, 3),
                        round($q->max_time_sec, 3),
                        number_format($q->total_rows_examined),
                    ], $slowQueries)
                );
            }
        } catch (\Exception $e) {
            $this->error('Could not retrieve query statistics: ' . $e->getMessage());
        }

        // Check for missing indexes
        $this->info("\nChecking for missing indexes...");
        $this->checkMissingIndexes();

        // Provide optimization tips
        $this->info("\n📊 Optimization Tips:");
        $this->info("1. Add indexes to frequently queried columns");
        $this->info("2. Use eager loading (with/load) to avoid N+1 queries");
        $this->info("3. Cache expensive queries (use Redis)");
        $this->info("4. Use query builders instead of raw SQL");
        $this->info("5. Paginate large result sets");
        $this->info("6. Use database indexing for foreign keys");
        $this->info("7. Consider query result caching for static data");

        return 0;
    }

    protected function checkMissingIndexes()
    {
        // Check common patterns that should have indexes
        $checks = [
            ['table' => 'jobs', 'column' => 'company_id', 'type' => 'Foreign Key'],
            ['table' => 'jobs', 'column' => 'status', 'type' => 'Filter'],
            ['table' => 'applications', 'column' => 'job_id', 'type' => 'Foreign Key'],
            ['table' => 'applications', 'column' => 'user_id', 'type' => 'Foreign Key'],
            ['table' => 'applications', 'column' => 'status', 'type' => 'Filter'],
            ['table' => 'users', 'column' => 'email', 'type' => 'Unique'],
        ];

        $missingIndexes = [];

        foreach ($checks as $check) {
            if (!$this->hasIndex($check['table'], $check['column'])) {
                $missingIndexes[] = $check;
            }
        }

        if (empty($missingIndexes)) {
            $this->info('✓ All critical indexes are present');
        } else {
            $this->warn('Missing indexes detected:');
            $this->table(
                ['Table', 'Column', 'Type'],
                array_map(fn($idx) => [
                    $idx['table'],
                    $idx['column'],
                    $idx['type'],
                ], $missingIndexes)
            );
        }
    }

    protected function hasIndex(string $table, string $column): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Column_name = ?", [$column]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
}
