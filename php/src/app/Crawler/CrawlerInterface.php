<?php

namespace App\Crawler;

use App\Enums\CrawlerResultEnum;

interface CrawlerInterface
{
    const CACHE_KEY_SEPARATOR = '_';

    public function crawlAll(string $username): void;

    public function getType(): string;

    public function initialSetup(?string $username = null, ?array $params = []): CrawlerResultEnum;
}
