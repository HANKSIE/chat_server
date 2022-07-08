<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MeiliSearch\Client;

class UpdateSearchableAttrs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchable:update-attrs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Meilisearch SearchableAttributes.';

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
        $client = new Client(config('scout.meilisearch.host'), config('scout.meilisearch.key'));
        $searchableAttrs = config('meilisearch.searchable_attrs');
        $this->info('start');
        foreach ($searchableAttrs as $index => $attrs) {
            $this->line("update '$index' index..");
            $client->index($index)->updateSearchableAttributes($attrs);
        }
        $this->info('finish');
    }
}
