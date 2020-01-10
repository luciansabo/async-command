<?php
require_once '../vendor/autoload.php';

use Lucian\AsyncCommand\EntryPoint\PhpScriptEntryPoint;
use Lucian\AsyncCommand\WorkerPool;

$urls = [
    'http://google.ro',
    'http://bing.com',
    'http://yahoo.ro',
    'http://google.ro',
    'http://bing.com',
    'http://yahoo.ro',
    'http://google.ro',
    'http://bing.com',
    'http://yahoo.ro',
    'http://yahoo.ro',
    'http://google.ro',
    'http://bing.com',
];

$t0 = microtime(true);

$workerPool = new WorkerPool(null, 10);

$entryPoint = new PhpScriptEntryPoint(__FILE__, 'workerCode');

foreach ($urls as $key => $url) {
    $promise = $workerPool->runAsync($entryPoint, $url, $key);
    $promise->then(
        function ($value) {
            // do something with the value
            var_dump($value);
            return $value;
        },
        function ($reason) {
            echo "\n\nErrors:\n$reason\n";
            return $reason;
        }
    );
}

$workerPool->wait();

function workerCode(string $url, int $delay)
{
    $contents = file_get_contents($url);
    // exceptions or fatal errors should result in a fail
    //throw new \Exception($url . ' caca');
    //trigger_error("Warning", E_WARNING);
    //trigger_error("Fatal error", E_USER_ERROR);

    return [$url, strlen($contents)];
}

echo 'Time: ' . (microtime(true) - $t0);
