<?php
require_once dirname(__FILE__) . '/../../../vendor/autoload.php';

use Lucian\AsyncCommand\EntryPoint\PhpScriptEntryPoint;
use Lucian\AsyncCommand\WorkerPool;

class SampleObjectMethodEntrypoint
{
    private $workerPool;

    public function __construct()
    {
        $this->workerPool = new WorkerPool($this, 4);
    }

    public function execute()
    {
        $entryPoint = new PhpScriptEntryPoint(__FILE__, 'this:workerCode');

        $param2 = 'test';
        for ($i = 0; $i < 10; $i++) {
            $promise = $this->workerPool->runAsync($entryPoint, $i, $param2);
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

        $this->workerPool->wait();
    }

    public function workerCode(int $counter, string $param2)
    {
        return $param2 . $counter;
    }
}

$app = new SampleObjectMethodEntrypoint();
$app->execute();
