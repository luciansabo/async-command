<?php


namespace Lucian\AsyncCommand\EntryPoint;


use Symfony\Component\Console\Command\Command;

class SymfonyCommandEntryPoint extends AbstractEntryPoint
{
    public const DEFAULT_BASE_COMMAND = [PHP_BINARY, 'bin/console'];

    public function __construct(Command $command, string $method)
    {
        if (empty($method)) {
            throw new \InvalidArgumentException('Entry-point myst not be empty.');
        }

        $finalCommand = static::DEFAULT_BASE_COMMAND;
        $finalCommand[] = $command->getName();
        $this->setCommand($finalCommand);
        $this->setEntryPoint("this:$method");
    }
}
