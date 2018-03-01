<?php

namespace eiriksm\CosyComposerTest\unit;

use eiriksm\CosyComposer\Message;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    public function testMethods()
    {
        $time = time();
        $msg = new Message('test', Message::PR_URL);
        $this->assertEquals('test', $msg->getMessage());
        $this->assertEquals(Message::PR_URL, $msg->getType());
        $this->assertEquals(true, ($time <= $msg->getTimestamp()));
    }
}
