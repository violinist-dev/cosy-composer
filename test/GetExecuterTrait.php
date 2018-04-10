<?php

namespace eiriksm\CosyComposerTest;

use eiriksm\CosyComposer\CommandExecuter;

trait GetExecuterTrait
{

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockExecuterWithReturnCallback($function)
    {
        $mock_executer = $this->createMock(CommandExecuter::class);
        $mock_executer->method('executeCommand')
            ->will($this->returnCallback($function));
        return $mock_executer;
    }
}
