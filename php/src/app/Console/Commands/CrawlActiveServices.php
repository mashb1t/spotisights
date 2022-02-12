<?php

namespace App\Console\Commands;

use App\Factory;
use App\Session\SessionHandler;
use Exception;
use Illuminate\Console\Command;

class CrawlActiveServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:active';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl data from all active services';

    protected Factory $factory;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->factory = new Factory();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $crawlers = $this->factory->getActiveCrawlers();
        $crawlerCount = count($crawlers);

        $this->info("found $crawlerCount active service(s): " . implode(', ', array_keys($crawlers)));

        if ($crawlerCount === 0) {
            $this->info('done');

            return 0;
        }

        foreach ($crawlers as $service => $crawler) {
            $sessionFiles = glob(
                SessionHandler::BASE_FILEPATH . DIRECTORY_SEPARATOR . $service . DIRECTORY_SEPARATOR . '*' . SessionHandler::SESSION_FILE_SUFFIX
            );
            $sessionFileCount = count($sessionFiles);

            $this->info("found $sessionFileCount $service session(s)");

            foreach ($sessionFiles as $index => $sessionFile) {
                $username = basename($sessionFile, SessionHandler::SESSION_FILE_SUFFIX);

                // show index +1 in outputs
                $index++;

                try {
                    $this->info("$index/$sessionFileCount: crawling user \"$username\"");
                    $crawler->crawlAll($username);

                } catch (Exception $exception) {
                    $this->error("$index/$sessionFileCount: exception while crawling $username, message: " . $exception->getMessage());
                }
            }

            $this->info('done');

            $this->newLine();
        }

        $this->info('done');

        return 0;
    }
}
