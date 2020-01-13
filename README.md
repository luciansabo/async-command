# Async-Command - Worker Pool implementation in PHP

This allows you to paralellize php code execution when used inside command line scripts.
The Worker Pool concept uses a fixed number of processes to execute a task. It is similar to multi-threading, but processes are used instead of threads.

## Quick start

This example shows how to make 4 HTTP GET requests in parallel:


```php
<?php

use Lucian\AsyncCommand\EntryPoint\PhpScriptEntryPoint;
use Lucian\AsyncCommand\WorkerPool;

// include composer autoloader
require_once 'vendor/autoload.php';

$urls = [
    'https://google.com',
    'https://bing.com',
    'https://yahoo.com',
    'https://amazon.com',
];

$workerPool = new WorkerPool(null, 4);

$entryPoint = new PhpScriptEntryPoint(__FILE__, 'workerCode');

foreach ($urls as $key => $url) {
    $promise = $workerPool->runAsync($entryPoint, $url, $key);
    $promise->then(
        function ($value) {
            // we receive what was returned from workerCode ([$url, strlen($contents)])
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

function workerCode(string $url)
{
    $contents = file_get_contents($url);
    // exceptions or fatal errors should result in a fail
    //throw new \Exception($url . ' caca');
    //trigger_error("Warning", E_WARNING);
    //trigger_error("Fatal error", E_USER_ERROR);

    return [$url, strlen($contents)];
}

```

`runAsync()` returns a Promise compatible with Promises/A+. The used Promise implementation is from Guzzle.

A promise represents the eventual result of an asynchronous operation. The primary way of interacting with a promise is through its `then` method, which registers callbacks to receive either a promise's eventual value or the reason why the promise cannot be fulfilled.

```php
use GuzzleHttp\Promise\PromiseInterface;

/** @var PromiseInterface $promise */

$promise = new Promise();
$promise->then(
    // $onFulfilled
    function ($value) {
        echo 'The promise was fulfilled.';
    },
    // $onRejected
    function ($reason) {
        echo 'The promise was rejected.';
    }
);
```

## Executing worker code from same class

To define a entry-point inside the current class pass `this:<method>` in the `PhpScriptEntryPoint` method argument.
Don't forget to build WorkerPool using `$this` as the parent argument.

```php
<?php

use Lucian\AsyncCommand\EntryPoint\PhpScriptEntryPoint;
use Lucian\AsyncCommand\WorkerPool;

require_once 'vendor/autoload.php';

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
```

`workerCode()` is a method inside the current class.

## Executing worker code from withing symfony commands

To define a entrypoint inside the current symfony command use `SymfonyCommandEntryPoint`:

```php
use Lucian\AsyncCommand\EntryPoint\SymfonyCommandEntryPoint;
use Lucian\AsyncCommand\WorkerPool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestAsyncCommand extends Command
{
    /**
     * @var WorkerPool
     */
    private $workerPool;

    public function __construct()
    {
        $this->workerPool = new WorkerPool($this, 4);

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entryPoint = new SymfonyCommandEntryPoint($this, 'workerCode');

        $param2 = 'test';
        for ($i = 0; $i < 10; $i++) {
            $promise = $this->workerPool->runAsync($entryPoint, $i, $param2);
            $promise->then(
                function ($value) {
                    // do something with the value
                    echo "$value\n";
                    return $value;
                }
            );
        }

        $this->workerPool->wait();
    }

    public function workerCode(int $counter, string $param2)
    {
        sleep(1); // simulate time consuming task

        return $param2 . $counter;
    }
}
```
