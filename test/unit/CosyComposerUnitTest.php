<?php

namespace eiriksm\CosyComposerTest\unit;

use eiriksm\CosyComposerTest\GetCosyTrait;
use eiriksm\CosyComposerTest\GetExecuterTrait;
use Psr\Log\LoggerInterface;

class CosyComposerUnitTest extends \PHPUnit_Framework_TestCase
{
    use GetCosyTrait;
    use GetExecuterTrait;

    public function testSetCacheDir()
    {
        $c = $this->getMockCosy();
        $test_cache_dir = '/tmp/something/here';
        $c->setCacheDir($test_cache_dir);
        $this->assertEquals($test_cache_dir, $c->getCacheDir());
    }

    public function testSetLogger()
    {
        $c = $this->getMockCosy();
        $test_logger = $this->createMock(LoggerInterface::class);
        $c->setLogger($test_logger);
        $this->assertEquals($test_logger, $c->getLogger());
    }
}
