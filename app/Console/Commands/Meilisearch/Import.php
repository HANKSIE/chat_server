<?php

namespace App\Console\Commands\Meilisearch;

use Illuminate\Console\Command;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:import {--flush}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import searchables.';

    public function handle()
    {
        $models = config('scout.meilisearch.imports');
        $flush = $this->option('flush');
        collect($models)->each(function ($model) use ($flush) {
            if ($flush) {
                $this->call('scout:flush', ['model' => $model]);
            }
            $this->call('scout:import', ['model' => $model]);
        });
    }
}
