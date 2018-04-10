<?php

namespace eiriksm\CosyComposerTest\unit;

use eiriksm\CosyComposer\CommandExecuter;
use eiriksm\CosyComposerTest\GetCosyTrait;
use eiriksm\CosyComposerTest\GetExecuterTrait;
use Psr\Log\LoggerInterface;

class CosyComposerUnitTest extends \PHPUnit_Framework_TestCase
{
    use GetCosyTrait;
    use GetExecuterTrait;

    public function testSetLogger()
    {
        $c = $this->getMockCosy();
        $test_logger = $this->createMock(LoggerInterface::class);
        $c->setLogger($test_logger);
        $this->assertEquals($test_logger, $c->getLogger());
    }

    public function testCacheDir()
    {
        $c = $this->getMockCosy();
        $bogus_dir = uniqid();
        $c->setCacheDir($bogus_dir);
        $this->assertEquals($bogus_dir, $c->getCacheDir());
    }

    public function testLastStdOut()
    {
        $c = $this->getMockCosy();
        $mock_exec = $this->createMock(CommandExecuter::class);
        $mock_exec->expects($this->once())
            ->method('getLastOutput')
            ->willReturn([
                'stdout' => 'output'
            ]);
        $c->setExecuter($mock_exec);
        $this->assertEquals('output', $c->getLastStdOut());
    }
}
