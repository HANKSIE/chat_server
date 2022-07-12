<?php

namespace App\Console\Commands\Meilisearch;

use Illuminate\Console\Command;
use MeiliSearch\Client;

class RemoveIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:remove-indexes {indexes*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove Meilisearch Searchable Indexes.';

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
        $client = app(Client::class);

        $indexes = $this->argument('indexes');
        $this->info('start');
        foreach ($indexes as $index) {
            $this->line("remove '$index' index");
            $client->index($index)->delete();
        }
        $this->info('finish');
    }
}
