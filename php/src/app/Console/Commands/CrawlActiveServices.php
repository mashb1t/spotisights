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
    public function handle(): int
    {
        logs('crawler')->info("running crawl:active");

        $crawlers = $this->factory->getActiveCrawlers();
        $crawlerCount = count($crawlers);
        logs('crawler')->info("found $crawlerCount active crawlers");

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
            logs('crawler')->info("found $sessionFileCount $service session(s)");

            $sessionFileIterator = $this->getOutput()->progressIterate($sessionFiles);
            foreach ($sessionFileIterator as $sessionFile) {
                $username = basename($sessionFile, SessionHandler::SESSION_FILE_SUFFIX);

                try {
                    $crawler->crawlAll($username);
                } catch (Exception $exception) {
                    $this->error("exception while crawling $username, message: " . $exception->getMessage());
                }
            }
            $this->info("done crawling $service");
            logs('crawler')->info("done crawling $service");

        }

        $this->info('done');
        logs('crawler')->info("finished crawl:active");

        return 0;
    }
}
