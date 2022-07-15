<?php

namespace App\Console\Commands\Meilisearch;

use Illuminate\Console\Command;
use MeiliSearch\Client;

class Initialize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'init meilisearch setting.';

    public function handle()
    {
        $client = app(Client::class);
        $config = config('scout.meilisearch.settings');
        $this->info('start');
        $this->newLine();
        collect($config)
            ->each(function ($settings, $class) use ($client) {
                $this->info($class);
                $model = new $class;
                $index = $client->index($model->searchableAs());
                collect($settings)
                    ->each(function ($params, $method) use ($index) {
                        $argsStr = implode(',', $params);
                        $this->line("$method: $argsStr");
                        $index->{$method}($params);
                    });
                $this->newLine();
            });
        $this->info('finish');
    }
}
