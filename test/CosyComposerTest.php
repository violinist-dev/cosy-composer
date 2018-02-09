<?php

namespace eiriksm\CosyComposerTest;

use eiriksm\CosyComposer\CosyComposer;
use eiriksm\CosyComposer\Exceptions\ChdirException;
use eiriksm\CosyComposer\Exceptions\GitCloneException;
use Mockery\Mock;
use Symfony\Component\Process\Process;

class CosyComposerTest extends \PHPUnit_Framework_TestCase
{

    private $logged_msgs = [];
    private $returnCode = 0;
    private $processCount = 0;

    public function noopLogger()
    {
        $this->logged_msgs[] = func_get_args();
        return $this->returnCode;
    }

    public function testChdirFail()
    {
        $app = $this->createMock('Composer\Console\Application');
        $output = $this->createMock('eiriksm\CosyComposer\ArrayOutput');

        $c = new CosyComposer('', 'a/b', $app, $output);
        $c->setTmpParent('/stupid/nonexistent');
        try {
            $c->run();
        } catch (ChdirException $e) {
            $this->assertEquals('Problem with changing dir to /stupid/nonexistent', $e->getMessage());
        }
    }

    public function testGitFail()
    {
        $app = $this->createMock('Composer\Console\Application');
        $output = $this->createMock('eiriksm\CosyComposer\ArrayOutput');
        $c = new CosyComposer('token', 'a/b', $app, $output);
        $p = \Mockery::mock(Process::class);
        $p->shouldReceive('run');
        $p->shouldReceive('setTimeout');
        $p->shouldReceive('getOutput');
        $p->shouldReceive('getErrorOutput');
        $p->shouldReceive('getExitCode')
        ->andReturn(42);
        $c->setProcessForCommand(sprintf('git clone --depth=1 https://:@github.com/a/b %s', $c->getTmpDir()), $p);
        try {
            $c->run();
        } catch (GitCloneException $e) {
            $this->assertEquals('Problem with the execCommand git clone. Exit code was 42', $e->getMessage());
        }
    }

    public function testChdirToCloneFail()
    {
        $app = $this->createMock('Composer\Console\Application');
        $output = $this->createMock('eiriksm\CosyComposer\ArrayOutput');
        $c = new CosyComposer('token', 'a/b', $app, $output);
        $p = \Mockery::mock(Process::class);
        $p->shouldReceive('run');
        $p->shouldReceive('setTimeout');
        $p->shouldReceive('getOutput');
        $p->shouldReceive('getErrorOutput');
        $p->shouldReceive('getExitCode')
        ->andReturn(0);
        $c->setProcessForCommand(sprintf('git clone --depth=1 https://:@github.com/a/b %s', $c->getTmpDir()), $p);
        try {
            $c->run();
        } catch (ChdirException $e) {
            $this->assertEquals('Problem with changing dir to the clone dir.', $e->getMessage());
        }
    }
}
