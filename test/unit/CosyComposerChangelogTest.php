<?php

namespace eiriksm\CosyComposerTest\unit;

use eiriksm\CosyComposerTest\GetCosyTrait;
use eiriksm\CosyComposerTest\GetExecuterTrait;

class CosyComposerChangelogTest extends \PHPUnit_Framework_TestCase
{
    use GetExecuterTrait;
    use GetCosyTrait;

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
