<?php

namespace eiriksm\CosyComposerTest\integration\issues;

use eiriksm\CosyComposer\CommandExecuter;
use eiriksm\CosyComposer\ProviderFactory;
use eiriksm\CosyComposer\Providers\Github;
use eiriksm\CosyComposerTest\integration\Base;

/**
 * Class Issue92Test.
 *
 * Issue 92 was that after we switched the updater package, the output from the failed composer update command would not
 * get logged.
 */
class Issue92Test extends Base
{
    public function testIssue92()
    {
        $c = $this->getMockCosy();
        $dir = '/tmp/' . uniqid();
        $this->setupDirectory($c, $dir);
        // Create a mock app, that can respond to things.
        $definition = $this->getMockDefinition();
        $mock_app = $this->getMockApp($definition);
        $c->setApp($mock_app);
        $mock_output = $this->getMockOutputWithUpdate('psr/log', '1.0.0', '1.0.2');
        $c->setOutput($mock_output);
        $this->placeComposerContentsFromFixture('composer-psr-log.json', $dir);
        $called = false;
        $mock_executer = $this->createMock(CommandExecuter::class);
        $current_error_output = '';
        $mock_executer->method('executeCommand')
            ->will($this->returnCallback(
                function ($cmd) use (&$called, &$current_error_output) {
                    $current_error_output = '';
                    if ($cmd == $this->createExpectedCommandForPackage('psr/log')) {
                        $current_error_output = "Trying to update\nFailed to update";
                    }
                    $a = 'b';
                    $return = 0;
                    if (strpos($cmd, 'rm -rf /tmp/') === 0) {
                        $called = true;
                    }
                    return $return;
                }
            ));
        $mock_executer->method('getLastOutput')
            ->willReturnCallback(function () use (&$current_error_output) {
                return [
                   'stdout' => '',
                   'stderr' =>  $current_error_output,
                ];
            });
        $c->setExecuter($mock_executer);
        $this->assertEquals(false, $called);

        // Then we are going to mock the provider factory.
        $this->registerProviderFactory($c);
        $this->assertEquals(false, $called);
        $this->placeComposerLockContentsFromFixture('composer-psr-log.lock', $dir);
        $c->run();
        $output = $c->getOutput();
        $this->assertOutputContainsMessage('Trying to update
Failed to update', $c);
        $this->assertEquals('psr/log was not updated running composer update', $output[16]->getMessage());
        $this->assertEquals(true, $called);
    }
}
