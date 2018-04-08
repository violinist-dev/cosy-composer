<?php

namespace eiriksm\CosyComposerTest\integration;

use Composer\Console\Application;
use eiriksm\ArrayOutput\ArrayOutput;
use eiriksm\CosyComposer\CommandExecuter;
use eiriksm\CosyComposer\CosyComposer;
use eiriksm\CosyComposerTest\GetCosyTrait;

class Base extends \PHPUnit_Framework_TestCase
{
    use GetCosyTrait;

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
