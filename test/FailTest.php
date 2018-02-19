<?php

namespace eiriksm\CosyComposerTest;

use eiriksm\CosyComposer\CommandExecuter;
use eiriksm\CosyComposer\Exceptions\ChdirException;

class FailTest extends Base
{

    public function testChdirFail()
    {
        $c = $this->getMockCosy();
        $c->setTmpParent('/stupid/nonexistent');
        $this->expectException(ChdirException::class);
        $this->expectExceptionMessage('Problem with changing dir to /stupid/nonexistent');
        $c->run();
    }

    public function testGitFail()
    {
        $c = $this->getMockCosy();
        $tmp_dir = '/tmp/' . uniqid();
        $c->setTmpDir($tmp_dir);
        $mock_executer = $this->createMock(CommandExecuter::class);
        $mock_executer->method('executeCommand')
            ->will($this->returnCallback(
                function ($cmd, $log = true, $timeout = 120) {

                    if (strpos($cmd, 'git clone --depth=1 https://:@github.com/a/b') === 0) {
                        return 42;
                    }
                    return 0;
                }
            ));
        $c->setExecuter($mock_executer);
        $this->expectExceptionMessage('Problem with the execCommand git clone. Exit code was 42');
        $c->run();
    }

    public function testChdirToCloneFail()
    {
        $c = $this->getMockCosy();
        $mock_executer = $this->createMock(CommandExecuter::class);
        $mock_executer->method('executeCommand')
            ->will($this->returnCallback(
                function ($cmd) {
                    return 0;
                }
            ));
        $this->expectExceptionMessage('Problem with changing dir to the clone dir.');
        $this->expectException(ChdirException::class);
        $c->setExecuter($mock_executer);
        $c->run();
    }
}
