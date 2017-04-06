<?php

namespace eiriksm\CosyComposerTest;

use eiriksm\CosyComposer\CosyComposer;
use eiriksm\CosyComposer\Exceptions\ChdirException;
use eiriksm\CosyComposer\Exceptions\GitCloneException;

class CosyComposerTest extends \PHPUnit_Framework_TestCase {

  private $logged_msgs = [];
  private $returnCode = 0;
  private $processCount = 0;

  public function noopLogger() {
    $this->logged_msgs[] = func_get_args();
    return $this->returnCode;
  }

  public function testChdirFail() {
    $c = new CosyComposer('', 'a/b');
    $c->setChdirCommand([$this, 'noopLogger']);
    try {
      $c->run();
    }
    catch (ChdirException $e) {
      $this->assertEquals('Problem with changing dir to /tmp', $e->getMessage());
    }
    $this->assertEquals($this->logged_msgs[0][0], '/tmp');
  }

  public function testGitFail() {
    $c = new CosyComposer('token', 'a/b');
    $c->setChdirCommand([$this, 'noopLogger']);
    $c->setPipes([
      1 => '',
      2 => '',
    ]);
    $c->setProcClose(function() {
      return 42;
    });
    $c->setProcOpen(function() {
      $this->processCount++;
      $this->logged_msgs[] = func_get_args();
      return $this->processCount;
    });
    $c->setContentGetter(function() {
    });
    $this->returnCode = 1;
    try {
      $c->run();
    }
    catch (GitCloneException $e) {
      $this->assertEquals('Problem with the execCommand git clone. Exit code was 42', $e->getMessage());
    }
    $this->assertEquals('git clone --depth=1 https://:@github.com/a/b ' . $c->getTmpDir(), $this->logged_msgs[1][0]);
  }

  public function testChdirToCloneFail() {
    $c = new CosyComposer('token', 'a/b');
    $c->setChdirCommand(function () {
      $this->processCount++;
      if ($this->processCount == 3) {
        return 0;
      }
      return 1;
    });
    $c->setPipes([
      1 => '',
      2 => '',
    ]);
    $c->setProcClose(function() {
      return 0;
    });
    $c->setProcOpen(function() {
      $this->processCount++;
      $this->logged_msgs[] = func_get_args();
      return 0;
    });
    $c->setContentGetter(function() {
    });
    $this->returnCode = 0;
    try {
      $c->run();
    }
    catch (ChdirException $e) {
      $this->assertEquals('Problem with changing dir to the clone dir.', $e->getMessage());
    }
  }
}
