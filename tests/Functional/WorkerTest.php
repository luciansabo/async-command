<?php


namespace Tests\Functional;


use PHPUnit\Framework\TestCase;

class WorkerTest extends TestCase
{

    public function testFunctionEntrypoint()
    {
        $this->runFixtureTest('function-entrypoint.php');
    }

    public function testObjectMethodEntrypoint()
    {
        $this->runFixtureTest('object-method-entrypoint.php');
    }

    private function runFixtureTest($fixture)
    {
        $oldDir = getcwd();
        chdir(dirname(__FILE__));
        $output = [];
        exec('php Fixture/' . $fixture, $output);

        // sort the output as it might come in a different order
        sort($output);

        $expected = [];
        for ($i = 0; $i < 10; $i++) {
            $expected[] = 'test' . $i;
        }

        $this->assertEquals($expected, $output);
        chdir($oldDir);
    }
}
