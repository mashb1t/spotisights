<?php

namespace App\Crawler;

enum CrawlerResultEnum: string {
    case SESSION_ALREADY_EXISTS = 'user already exists';
    case SESSION_ACCESS_TOKEN_ERROR = 'error while fetching access token';
    case SESSION_SETUP_SUCCESS = 'initial setup success';

    case CRAWL_FAILED = 'general error';
    case CRAWL_SUCCESSFUL = 'success';
}
