<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DBClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear {--seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all tables.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('start');
        $tables = DB::select('SHOW TABLES');
        $db = env('DB_DATABASE');
        // truncate all table
        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            $tableName = $table->{"Tables_in_{$db}"};
            DB::table($tableName)->truncate();
            $this->line("truncate {$tableName} table");
        }
        Schema::enableForeignKeyConstraints();
        $this->info('finish');
        if ($this->option('seed')) {
            $this->call('db:seed');
        }
    }
}
