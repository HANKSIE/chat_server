<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        $tables = DB::select('SHOW TABLES');
        $db = env('DB_DATABASE');
        // truncate all table
        foreach ($tables as $table) {
            DB::table($table->{"Tables_in_{$db}"})->truncate();
        }
        $this->call([
            UserSeeder::class,
        ]);
        Schema::enableForeignKeyConstraints();
    }
}
