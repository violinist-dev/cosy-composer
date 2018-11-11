<?php

namespace eiriksm\CosyComposerTest;

use Composer\Console\Application;
use eiriksm\ArrayOutput\ArrayOutput;
use eiriksm\CosyComposer\CommandExecuter;
use eiriksm\CosyComposer\CosyComposer;
use SensioLabs\Security\SecurityChecker;
use violinist\ProjectData\ProjectData;

trait GetCosyTrait
{
    protected function getMockCosy($dir = null)
    {
        $app = $this->createMock(Application::class);
        $output = $this->createMock(ArrayOutput::class);
        $executer = $this->createMock(CommandExecuter::class);
        $c = new CosyComposer('a/b', $app, $output, $executer);
        $p = new ProjectData();
        $p->setNid(123);
        $c->setProject($p);
        $c->setTokenUrl('http://localhost:9988');
        if ($dir) {
            mkdir($dir);
            $c->setTmpDir($dir);
        }
        $mock_checker = $this->createMock(SecurityChecker::class);
        $c->getCheckerFactory()->setChecker($mock_checker);
        return $c;
    }
}
