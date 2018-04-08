<?php

namespace eiriksm\CosyComposerTest\unit;

use eiriksm\CosyComposer\CosyComposer;
use eiriksm\CosyComposerTest\GetCosyTrait;

class CosyComposerUnitTest extends \PHPUnit_Framework_TestCase
{
    use GetCosyTrait;

    public function testSetCacheDir()
    {
        $c = $this->getMockCosy();
        $test_cache_dir = '/tmp/something/here';
        $c->setCacheDir($test_cache_dir);
        $this->assertEquals($test_cache_dir, $c->getCacheDir());
    }
}
