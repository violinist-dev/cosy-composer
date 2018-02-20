<?php

namespace eiriksm\CosyComposer;

use Psr\Log\LoggerInterface;

class CommandExecuter
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \eiriksm\CosyComposer\ProcessFactory
     */
    protected $processFactory;

    protected $cwd;

    public function __construct(LoggerInterface $logger, ProcessFactory $factory)
    {
        $this->logger = $logger;
        $this->processFactory = $factory;
    }

    public function executeCommand($command, $log = true, $timeout = 120)
    {
        if ($log) {
            $this->logger->info("Creating command $command");
        }
        $process = $this->processFactory->getProcess($command, $this->getCwd());
        $process->setTimeout($timeout);
        $process->run();
        return $process->getExitCode();
    }

    /**
     * @return mixed
     */
    public function getCwd()
    {
        return $this->cwd;
    }

    /**
     * @param mixed $cwd
     */
    public function setCwd($cwd)
    {
        $this->cwd = $cwd;
    }
}
