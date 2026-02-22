<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeDatabase extends Command
{
    protected $signature = 'db:optimize';
    protected $description = 'Optimize database tables and analyze query performance';

    public function handle()
    {
        $this->info('Starting database optimization...');

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $database = env('DB_DATABASE');
        $tableKey = "Tables_in_{$database}";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            $this->info("Optimizing {$tableName}...");
            
            // Analyze table
            DB::statement("ANALYZE TABLE {$tableName}");
            
            // Optimize table
            DB::statement("OPTIMIZE TABLE {$tableName}");
        }

        $this->info('✓ Database optimization completed!');

        // Show table statistics
        $this->info("\nTable Statistics:");
        $this->showTableStats();

        return 0;
    }

    protected function showTableStats()
    {
        $stats = DB::select("
            SELECT 
                table_name,
                table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                ROUND((index_length / 1024 / 1024), 2) AS index_size_mb
            FROM information_schema.TABLES
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
            LIMIT 10
        ", [env('DB_DATABASE')]);

        $this->table(
            ['Table', 'Rows', 'Size (MB)', 'Index Size (MB)'],
            array_map(fn($stat) => [
                $stat->table_name,
                number_format($stat->table_rows),
                $stat->size_mb,
                $stat->index_size_mb,
            ], $stats)
        );
    }
}
