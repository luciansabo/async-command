<?php


namespace Lucian\AsyncCommand\EntryPoint;


class PhpScriptEntryPoint extends AbstractEntryPoint
{
    public function __construct(string $file, string $method)
    {
        if (empty($method)) {
            throw new \InvalidArgumentException('Entry-point myst not be empty.');
        }

        $command = [PHP_BINARY, $file];

        $this->setCommand($command);
        $this->setEntryPoint($method);
    }
}
