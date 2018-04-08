<?php

namespace eiriksm\CosyComposerTest;

use Composer\Console\Application;
use eiriksm\ArrayOutput\ArrayOutput;
use eiriksm\CosyComposer\CommandExecuter;
use eiriksm\CosyComposer\CosyComposer;

trait GetCosyTrait
{
    protected function getMockCosy()
    {
        $app = $this->createMock(Application::class);
        $output = $this->createMock(ArrayOutput::class);
        $executer = $this->createMock(CommandExecuter::class);
        $c = new CosyComposer('token', 'a/b', $app, $output, $executer);
        return $c;
    }
}
