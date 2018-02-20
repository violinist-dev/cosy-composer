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
        $process = $this->processFactory->getProcess($command);
        $process->setTimeout($timeout);
        $process->run();
        return $process->getExitCode();
    }
}
