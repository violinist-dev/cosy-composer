<?php

namespace eiriksm\CosyComposer;

use Composer\Command\OutdatedCommand;
use Composer\Console\Application;
use Github\Client;
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Command\ShowCommand;
use Symfony\Component\Console\Input\InputOption;

class CosyComposer {

  /**
   * @var array
   */
  private $messages = [];

  /**
   * @var bool
   */
  private $verbose = FALSE;

  /**
   * @var string
   */
  protected $tmpDir;

  /**
   * @var array
   */
  private $consoleOutput = [];

  /**
   * @var Application
   */
  private $app;

  /**
   * @var string
   */
  private $token;

  /**
   * @var string
   */
  private $slug;

  public function __construct($token, $slug) {
    $this->token = $token;
    $this->slug = $slug;
  }

  public function setVerbose($verbose) {
    $this->verbose = $verbose;
  }

  public function run() {
    $repo = $this->slug;
    $repo_parts = explode('/', $repo);
    $user_name = $repo_parts[0];
    $user_repo = $repo_parts[1];
    $tmpdir = uniqid();
    $this->tmpDir = sprintf('/tmp/%s', $tmpdir);
    $clone_result = $this->execCommand('git clone --depth=1 git@github.com:' . $repo . '.git ' . $this->tmpDir);
    if ($clone_result) {
      // We had a problem.
      throw new \Exception('Problem with the execCommand git clone. Exit code was ' . $clone_result);
    }
    chdir($this->tmpDir);
    $outdated = new OutdatedCommand();
    $show = new ShowCommand();
    $this->app = new Application();
    $app = $this->app;
    $d = $app->getDefinition();
    $opts = $d->getOptions();
    $opts['no-ansi'] = new InputOption('no-ansi', NULL, 4, TRUE, 'Disable ANSI output');
    $d->setOptions($opts);
    $app->setDefinition($d);
    $app->setAutoExit(FALSE);
    $app->add($outdated);
    $app->add($show);
    $this->doComposerInstall();
    $outdated->setApplication($app);
    $show->setApplication($app);
    $def = $outdated->getDefinition();
    $i = new ArrayInput([], $def);
    $i->setOption('direct', TRUE);
    $i->setOption('minor-only', TRUE);
    $b = new ArrayOutput();
    $outdated->setInputBound(TRUE);
    $outdated->run($i, $b);
    $data = $b->fetch();
    $client = new Client();
    $client->authenticate($this->token, NULL, Client::AUTH_URL_TOKEN);
    foreach ($data as $item) {
      // @todo: Fix this properly.
      if ($item[0] == '<warning>You are running composer with xdebug enabled. This has a major impact on runtime performance. See https://getcomposer.org/xdebug</warning>') {
        continue;
      }
      // @todo: Fix this properly.
      $item[2] = str_replace('<highlight>!', '', $item[2]);
      $item[2] = str_replace('</highlight>', '', $item[2]);
      $item[2] = trim($item[2]);
      // Create a new branch.
      $branch_name = $this->createBranchName($item);
      $this->execCommand('git checkout -b ' . $branch_name);
      $command = 'composer update --with-dependencies ' . $item[0];
      $this->execCommand($command);
      $this->execCommand('git commit composer.* -m "Update ' . $item[0] . '"');
      $this->execCommand('git push origin ' . $branch_name);
      $this->execCommand('git checkout master');
      $pullRequest = $client->api('pull_request')->create($user_name, $user_repo, array(
        'base'  => 'master',
        'head'  => $branch_name,
        'title' => $this->createTitle($item),
        'body'  => $this->createBody($item),
      ));
    }
    // Clean up.
    $this->cleanUp();
  }

  public function getOutput() {
    return $this->consoleOutput;
  }

  private function cleanUp() {
    $this->execCommand('rm -rf ' . $this->tmpDir);
  }

  protected function createTitle($item) {
    return sprintf('Update %s from %s to %s', $item[0], $item[1], $item[2]);
  }

  protected function createBody($item) {
    // @todo: Change this to something different.
    return $this->createTitle($item);
  }

  protected function createBranchName($item) {
    $this->debug(['Creating branch name based on', $item]);
    $item_string = sprintf('%s%s%s', $item[0], $item[1], $item[2]);
    // @todo: Fix this properly.
    $result = preg_replace('/[^a-zA-Z0-9]+/', '', $item_string);
    $this->debug('Creating branch named', $result);
    return $result;
  }

  protected function execCommand($command) {
    $this->debug(['Creating command', $command]);
    $descriptor_spec = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w'],
      2 => ['pipe', 'w'],
    ];
    $pipes = [];
    $process = proc_open($command, $descriptor_spec, $pipes, getcwd(), NULL);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    $this->debug([
      'stdout' => $stdout,
      'stderr' => $stderr,
    ]);
    $returnCode = proc_close($process);
    return $returnCode;
  }

  protected function debug() {
    $message = func_get_args();
    if ($this->verbose) {
      print_r($message);
    }
    $this->messages[] = $message;
  }

  protected function doComposerInstall() {
    $this->execCommand('composer install');
  }
}
