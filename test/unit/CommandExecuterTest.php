<?php

namespace eiriksm\CosyComposerTest\unit;

use eiriksm\CosyComposer\CommandExecuter;
use eiriksm\CosyComposer\Message;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Process\Process;

class CommandExecuterTest extends PHPUnit_Framework_TestCase
{
    public function testExecuteCommand()
    {
        $process_mock = $this->createMock(\eiriksm\CosyComposer\ProcessFactory::class);
        $logger_mock = $this->createMock(\Psr\Log\LoggerInterface::class);
        $command = 'echo eirik';
        $logger_mock->expects($this->once())
            ->method('log')
            ->with('info', new Message("Creating command $command", Message::COMMAND));
        $ce = new CommandExecuter($logger_mock, $process_mock);
        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('setTimeout')
            ->with(120);
        $process->expects($this->once())
            ->method('run');
        $process_mock->expects($this->once())
            ->method('getProcess')
            ->willReturn($process);
        $ce->setCwd('eirik');
        $this->assertEquals($ce->getCwd(), 'eirik');
        $ce->executeCommand($command, true);
    }
}
