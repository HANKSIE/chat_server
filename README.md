# Boot Step

1. Install dependencies

    `composer install`

2. Add a .env file base on .env.example
3. Generate key

    `php artisan key:generate`

4. Build the environment

    `cd ./environments/dev/ && docker-compose --env-file ../../.env up -d`

5. Initialize Meilisearch

    `php artisan meilisearch:init`

6. If 'SCOUT_QUEUE' in the .env is 'true', run queue worker command:
   `php artisan queue:work` or `php artisan queue:listen`

7. Migrate and Seed

    `php artisan migrate --seed`

8. Link Storage folder

    `php artisan storage:link`

## Neccessary Processes

`php artisan serve`

`php artisan queue:work` or `php artisan queue:listen`
