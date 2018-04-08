<?php

namespace eiriksm\CosyComposerTest\integration;

use eiriksm\ArrayOutput\ArrayOutput;
use eiriksm\CosyComposer\CommandExecuter;
use eiriksm\CosyComposer\CosyComposer;

class Base extends \PHPUnit_Framework_TestCase
{

    protected function getMockCosy()
    {
        $app = $this->createMock('Composer\Console\Application');
        $output = $this->createMock(ArrayOutput::class);
        $executer = $this->createMock(CommandExecuter::class);
        $c = new CosyComposer('token', 'a/b', $app, $output, $executer);
        return $c;
    }

    protected function getMockExecuterWithReturnCallback($function)
    {
        $mock_executer = $this->createMock(CommandExecuter::class);
        $mock_executer->method('executeCommand')
            ->will($this->returnCallback($function));
        return $mock_executer;
    }

    protected function createComposerFileFromFixtures($dir, $filename)
    {
        $composer_contents = file_get_contents(__DIR__ . "/../fixtures/$filename");
        $composer_file = "$dir/composer.json";
        file_put_contents($composer_file, $composer_contents);
    }
}
