<?php

namespace App\Console\Commands\Meilisearch;

use Illuminate\Console\Command;

class Flush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'flush all indexes.';

    public function handle()
    {
        $models = config('scout.meilisearch.imports');
        collect($models)->each(function ($model) {
            $this->call('scout:flush', ['model' => $model]);
        });
    }
}
