<?php

namespace eiriksm\CosyComposerTest\unit;

use Composer\Console\Application;
use eiriksm\CosyComposer\CommandExecuter;
use eiriksm\CosyComposer\CosyComposer;
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

    public function testChangeLogPackageNotFound()
    {
        $c = $this->getMockCosy();
        // Of course this should not be possible, but what does one do for coverage, eh?
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Did not find the requested package (vendor/package) in the lockfile. This is probably an error');
        $c->retrieveChangeLog('vendor/package', (object) ['packages' => [], 'packages-dev' => []], 1, 2);
    }

    public function testChangeLogRepoUnknownSource()
    {
        $c = $this->getMockCosy();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown source or non-git source found for vendor/package. Aborting.');
        $c->retrieveChangeLog('vendor/package', json_decode(json_encode(['packages' => [
            [
                'name' => 'vendor/package',
            ],
        ]])), 1, 2);
    }

    public function testChangeLogRepoCloneError()
    {
        $c = $this->getMockCosy();
        $called = false;
        $mock_executer = $this->getMockExecuterWithReturnCallback(function ($command) use (&$called) {
            if (strpos($command, 'git clone http://example.com/vendor/package /tmp/') === 0) {
                $called = true;
            }
            return 0;
        });
        $c->setExecuter($mock_executer);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The changelog string was empty for package vendor/package');
        $c->retrieveChangeLog('vendor/package', json_decode(json_encode(['packages' => [
            [
                'name' => 'vendor/package',
                'source' => [
                    'type' => 'git',
                    'url' => 'http://example.com/vendor/package',
                ],
            ],
        ]])), 1, 2);
        $this->assertEquals(true, $called);
    }
}
