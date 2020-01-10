<?php
require_once dirname(__FILE__) . '/../../../vendor/autoload.php';

use Lucian\AsyncCommand\EntryPoint\PhpScriptEntryPoint;
use Lucian\AsyncCommand\WorkerPool;

$workerPool = new WorkerPool(null, 4);

$entryPoint = new PhpScriptEntryPoint(__FILE__, 'workerCode');

$param2 = 'test';
for ($i = 0; $i < 10; $i++) {
    $promise = $workerPool->runAsync($entryPoint, $i, $param2);
    $promise->then(
        function ($value) {
            // do something with the value
            echo "$value\n";
            return $value;
        },
        function ($reason) {
            echo "\n\nErrors:\n$reason\n";
            return $reason;
        }
    );
}

$workerPool->wait();

function workerCode(int $counter, string $param2)
{
    // exceptions or fatal errors should result in a fail
    //throw new \Exception($counter . ' error');
    //trigger_error("Warning", E_WARNING);
    //trigger_error("Fatal error", E_USER_ERROR);

    return $param2 . $counter;
}
