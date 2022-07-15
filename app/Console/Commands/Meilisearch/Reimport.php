<?php

namespace App\Console\Commands\Meilisearch;

use Illuminate\Console\Command;

class Reimport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:reimport';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import searchables.';

    public function handle()
    {
        $models = config('scout.meilisearch.imports');
        collect($models)->each(function ($model) {
            $this->call('scout:flush', ['model' => $model]);
            $this->call('scout:import', ['model' => $model]);
        });
    }
}
