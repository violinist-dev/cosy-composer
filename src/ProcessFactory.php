<?php

namespace eiriksm\CosyComposer;

use Symfony\Component\Process\Process;

class ProcessFactory
{

    public function getProcess($cmd, $cwd = null)
    {
        if (!$cwd) {
            $cwd = $this->getCwd();
        }
        return new Process($cmd, $cwd);
    }

    protected function getCwd()
    {
        return getcwd();
    }
}
