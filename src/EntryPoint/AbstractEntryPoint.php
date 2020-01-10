<?php


namespace Lucian\AsyncCommand\EntryPoint;


abstract class AbstractEntryPoint
{
    /**
     * @var array`
     */
    private $command;
    /** @var string */
    private $entryPoint;

    /**
     * @param array $command
     */
    public function setCommand(array $command): void
    {
        $this->command = $command;
    }

    /**
     * @param string $entryPoint
     */
    public function setEntryPoint(string $entryPoint): void
    {
        $this->entryPoint = $entryPoint;
    }

    /**string
     * @return string
     */
    public function getCommand(): array
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getEntryPoint(): string
    {
        return $this->entryPoint;
    }
}
