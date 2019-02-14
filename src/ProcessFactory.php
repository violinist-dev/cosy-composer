<?php

namespace eiriksm\CosyComposer;

use Symfony\Component\Process\Process;
use Violinist\ProcessFactory\ProcessFactoryInterface;

class ProcessFactory implements ProcessFactoryInterface
{

    public function getProcess($commandline, $cwd = null, array $env = null, $input = null, $timeout = 60, array $options = null)
    {
        if (!$cwd) {
            $cwd = $this->getCwd();
        }
        return new Process($commandline, $cwd);
    }

    protected function getCwd()
    {
        return getcwd();
    }
}
