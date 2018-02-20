<?php

namespace eiriksm\CosyComposerTest\integration;

use eiriksm\CosyComposer\CosyComposer;

class Base extends \PHPUnit_Framework_TestCase
{

    protected function getMockCosy()
    {
        $app = $this->createMock('Composer\Console\Application');
        $output = $this->createMock('eiriksm\CosyComposer\ArrayOutput');
        $executer = $this->createMock('eiriksm\CosyComposer\CommandExecuter');
        $c = new CosyComposer('token', 'a/b', $app, $output, $executer);
        return $c;
    }
}
