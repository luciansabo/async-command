<?php

namespace Tests\Unit;

use Lucian\AsyncCommand\WorkerPool;
use PHPUnit\Framework\TestCase;

class WorkerPoolTest extends TestCase
{

    public function testConstructWithDefaults()
    {
        $workerPool = new WorkerPool();
        $this->assertInstanceOf(WorkerPool::class, $workerPool);
        $this->assertEquals(WorkerPool::DEFAULT_NUM_WORKERS, $workerPool->getNumWorkers());
    }

    public function testConstructWithCustomParams()
    {
        $numWorkers = 10;
        $workerPool = new WorkerPool(null, $numWorkers);
        $this->assertInstanceOf(WorkerPool::class, $workerPool);
        $this->assertEquals($numWorkers, $workerPool->getNumWorkers());
    }
}
