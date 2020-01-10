<?php

namespace Lucian\AsyncCommand;

use GuzzleHttp\Promise\Promise;
use Lucian\AsyncCommand\EntryPoint\AbstractEntryPoint;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class WorkerPool
{
    public const OPT_WORKER = 'asynccommand1';
    public const OPT_WORKER_ENTRYPOINT = 'worker-entrypoint';
    public const DEFAULT_NUM_WORKERS = 2;

    /**
     * @var int
     */
    private $numWorkers;
    /**
     * @var Process[]
     */
    private $processes = [];

    /**
     * @var Promise[]
     */
    private $promises = [];

    /**
     * @var array
     */
    private $commandLineOptions;
    /**
     * @var object
     */
    private $parent;


    public function __construct(?object $parent = null, int $numWorkers = self::DEFAULT_NUM_WORKERS)
    {
        $this->parent = $parent;
        $this->numWorkers = $numWorkers;
        $this->commandLineOptions = $this->getCommandLineOptions();

        if ($this->isCurrentProcessAWorker()) {
            $this->runWorkerCode();
        }
    }

    public function runAsync(AbstractEntryPoint $entryPoint, ...$params)
    {
        // wait for a free worker
        if (count($this->processes) >= $this->numWorkers) {
            $this->wait();
        }

        $process = new Process(
            array_merge(
                $entryPoint->getCommand(),
                [
                    '--' . static::OPT_WORKER,
                    '--' . static::OPT_WORKER_ENTRYPOINT . '=' . $entryPoint->getEntrypoint(),
                ]
            )
        );

        $processId = spl_object_hash($process);

        $this->processes[$processId] = $process;
        $input = new InputStream();
        $process->setInput($input);
        $process->start();

        $input->write($this->serialize($params));
        $input->close();
        $promise = new Promise();
        $this->promises[$processId] = $promise;

        return $promise;
    }

    /**
     * @return int
     */
    public function getNumWorkers(): int
    {
        return $this->numWorkers;
    }

    protected function isCurrentProcessAWorker(): bool
    {
        return isset($this->commandLineOptions['--' . static::OPT_WORKER]);
    }

    protected function resolvePromise($processId, $value)
    {
        $this->promises[$processId]->resolve($value);
    }

    protected function rejectPromise($processId, $reason)
    {
        $this->promises[$processId]->reject($reason);
    }

    /**
     * wait for processes to finish
     */
    function wait()
    {
        $contents = [];

        do {
            foreach ($this->processes as $key => $process) {
                if (!isset($contents[$key])) {
                    $contents[$key] = '';
                }

                if (($buffer = $process->getIncrementalOutput()) !== '') {
                    $contents[$key] .= $buffer;
                }

                if (!$process->isRunning()) {
                    // note that isset returns false for null
                    if (!empty($contents[$key])) {
                        $message = $this->unserialize($contents[$key]);
                        if ($message !== false || $contents[$key] === serialize(false) ) {
                            $this->resolvePromise($key, $message);
                        } else {
                            $this->rejectPromise($key, 'Corrupted message from worker: '. $contents[$key]);
                        }
                    } else {
                        $this->rejectPromise($key, $process->getIncrementalErrorOutput());
                    }
                    unset($contents[$key]);
                    unset($this->processes[$key]);
                    unset($this->promises[$key]);
                }

            }
            usleep(1000);
        } while ($this->processes);
    }

    private function getMessage()
    {
        static $stdin;
        if (!isset($stdin)) {
            $stdin = fopen('php://stdin', 'r');
        }
        $rawData = stream_get_contents($stdin);
        if ($rawData) {
            return $this->unserialize($rawData);
        }

        return null;
    }

    private function sendException(\Exception $exception)
    {
        static $stderr;
        if (!isset($stderr)) {
            $stderr = fopen('php://stderr', 'w');
        }

        fwrite(
            $stderr,
            get_class($exception) . ' at line ' . $exception->getLine() . ' with message "' . $exception->getMessage(
            ) . '"'
        );
    }

    private function serialize($data)
    {
        return serialize($data);
    }

    private function unserialize($data)
    {
        return unserialize($data);
    }

    private function getCommandLineOptions()
    {
        $options = [];
        foreach ($_SERVER["argv"] as $key => $arg) {
            if (strpos($arg, '--') === 0) {
                $opts = explode('=', $arg);
                if (count($opts) == 2) {
                    $options[$opts[0]] = $opts[1];
                } else {
                    $options[$arg] = '';
                }
            }
        }

        return $options;
    }

    private function runWorkerCode()
    {
        // run worker code
        $entryPointString = $this->commandLineOptions['--' . static::OPT_WORKER_ENTRYPOINT] ?? null;
        $entryPoint = strpos($entryPointString, 'this:') === 0 ?
            [$this->parent, substr($entryPointString, 5)] : $entryPointString;

        try {
            if (!$entryPoint) {
                throw new \RuntimeException(
                    sprintf(
                        'No entry-point provided for worker. Pass option --%s=<yourMethod>',
                        static::OPT_WORKER_ENTRYPOINT
                    )
                );
            }

            $receivedMessage = $this->getMessage();

            if ($receivedMessage) {
                // suppress output to not get corrupted messages
                ob_start();
                if (is_array($receivedMessage)) {
                    $return = call_user_func($entryPoint, ...$receivedMessage);
                } else {
                    $return = $entryPoint($receivedMessage);
                }
                ob_end_clean();

                echo $this->serialize($return);
            }

        } catch (\Exception $e) {
            $this->sendException($e);
        } finally {
            // we need to prevent execution of master code
            exit;
        }

    }
}
