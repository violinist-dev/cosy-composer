<?php

namespace eiriksm\CosyComposer;

use Symfony\Component\Process\Process;

class ProcessWrapper extends Process
{
    protected $ourExitCode;

    /**
     * @var CommandExecuter
     */
    protected $executor;

    /**
     * @return CommandExecuter
     */
    public function getExecutor()
    {
        return $this->executor;
    }

    /**
     * @param CommandExecuter $executor
     */
    public function setExecutor(CommandExecuter $executor)
    {
        $this->executor = $executor;
    }

    public function run($callback = null/*, array $env = array()*/)
    {
        $this->ourExitCode = $this->executor->executeCommand($this->getCommandLine(), false, 600);
    }

    public function getExitCode()
    {
        return $this->ourExitCode;
    }

    public function getErrorOutput()
    {
        if (!$this->isStarted()) {
            return '';
        }
        return parent::getErrorOutput();
    }
}