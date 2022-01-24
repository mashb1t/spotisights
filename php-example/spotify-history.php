<?php

declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

$statsd = new League\StatsD\Client();
$statsd->configure([
    'host'      => '127.0.0.1',
    'port'      => 8125,
    'namespace' => 'spotify'
]);

$ops = 0;
$requestsSent = 0;

$startTime = microtime(true);

pcntl_async_signals(true);
pcntl_signal(SIGINT, static function () use (&$ops, $startTime, &$requestsSent) {
    $runtime = microtime(true) - $startTime;
    $opsPerSecond = $ops / $runtime;
    $requestsPerSecond = $requestsSent / $runtime;
    echo PHP_EOL;
    echo "Runtime:\t${runtime} Seconds\n";
    echo "Ops:\t\t${ops} \n";
    echo "Ops/s:\t\t${opsPerSecond} \n";
    echo "Requests Sent:\t${requestsSent} \n";
    echo "Requests/s:\t${requestsPerSecond} \n";
    echo "Killed by Ctrl+C\n";
    exit(0);
});

    $statsd->increment('request.successful.count,type=' . $type, $delta);
    $statsd->set('request.successful.time,type=' . $type, $time);
