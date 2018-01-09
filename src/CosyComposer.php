<?php

namespace eiriksm\CosyComposer;

use Composer\Console\Application;
use eiriksm\CosyComposer\Exceptions\ChdirException;
use eiriksm\CosyComposer\Exceptions\ComposerInstallException;
use eiriksm\CosyComposer\Exceptions\GitCloneException;
use eiriksm\CosyComposer\Exceptions\GitPushException;
use eiriksm\CosyComposer\Exceptions\NotUpdatedException;
use eiriksm\GitLogFormat\ChangeLogData;
use eiriksm\ViolinistMessages\ViolinistMessages;
use eiriksm\ViolinistMessages\ViolinistUpdate;
use Github\Client;
use Github\Exception\RuntimeException;
use Github\Exception\ValidationFailedException;
use Github\HttpClient\Builder;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class CosyComposer {

  /**
   * @var Process[]
   */
  protected $processesForCommands = [];

  /**
   * @var array
   */
  private $messages = [];

  /**
   * @return string
   */
  public function getCacheDir() {
    return $this->cacheDir;
  }

  /**
   * @param string $cacheDir
   */
  public function setCacheDir($cacheDir) {
    $this->cacheDir = $cacheDir;
  }

  /**
   * @var string
   */
  private $cacheDir = '/tmp';

  /**
   * @var bool
   */
  private $verbose = FALSE;

  /**
   * @var string
   */
  protected $tmpDir;

  /**
   * @return string
   */
  public function getTmpParent() {
    return $this->tmpParent;
  }

  /**
   * @param string $tmpParent
   */
  public function setTmpParent($tmpParent) {
    $this->tmpParent = $tmpParent;
  }

  /**
   * @var string
   */
  protected $tmpParent = '/tmp';

  /**
   * @var Application
   */
  private $app;

  /**
   * @return string
   */
  public function getCwd() {
    return $this->cwd;
  }

  /**
   * @var string
   */
  protected $cwd;

  /**
   * @var string
   */
  private $token;

  /**
   * @var string
   */
  private $slug;

  /**
   * @var string
   */
  private $githubUser;

  /**
   * @var string
   */
  private $githubPass;

  /**
   * @var string
   */
  private $forkUser;

  /**
   * @var mixed
   */
  private $proc_open = 'proc_open';

  private $proc_close = 'proc_close';

  private $pipes = [];

  private $contentGetter = 'stream_get_contents';

  private $githubUserName;
  private $githubUserPass;
  private $githubEmail;
  private $messageFactory;

  /**
   * @var string
   */
  private $lastStdErr = '';

  /**
   * @var string
   */
  private $lastStdOut = '';

  /**
   * @return string
   */
  public function getLastStdErr() {
    return $this->lastStdErr;
  }

  /**
   * @param string $lastStdErr
   */
  public function setLastStdErr($lastStdErr) {
    $this->lastStdErr = $lastStdErr;
  }

  /**
   * @return string
   */
  public function getLastStdOut() {
    return $this->lastStdOut;
  }

  /**
   * @param string $lastStdOut
   */
  public function setLastStdOut($lastStdOut) {
    $this->lastStdOut = $lastStdOut;
  }

  /**
   * CosyComposer constructor.
   * @param string $token
   * @param string $slug
   */
  public function __construct($token, $slug) {
    $this->token = $token;
    $this->slug = $slug;
    $tmpdir = uniqid();
    $this->tmpDir = sprintf('/tmp/%s', $tmpdir);
    $this->messageFactory = new ViolinistMessages();
  }

  public function setVerbose($verbose) {
    $this->verbose = $verbose;
  }

  public function setGithubAuth($user, $pass) {
    $this->githubUser = $user;
    $this->forkUser = $user;
    $this->githubPass = $pass;
  }

  public function setGithubForkAuth($user, $pass, $mail) {
    $this->githubUserName = $user;
    $this->githubUserPass = $pass;
    $this->githubEmail = $mail;
  }

  /**
   * Set a user to fork to.
   *
   * @param string $user
   */
  public function setForkUser($user) {
    $this->forkUser = $user;
  }

  /**
   * @throws \eiriksm\CosyComposer\Exceptions\ChdirException
   * @throws \eiriksm\CosyComposer\Exceptions\GitCloneException
   */
  public function run() {
    $this->log(sprintf('Starting update check for %s', $this->slug));
    $repo = $this->slug;
    $repo_parts = explode('/', $repo);
    $user_name = $repo_parts[0];
    $user_repo = $repo_parts[1];
    // First set working dir to /tmp (since we might be in the directory of the
    // last processed item, which may be deleted.
    if (!$this->chdir($this->getTmpParent())) {
      throw new ChdirException('Problem with changing dir to ' . $this->getTmpParent());
    }
    $url = sprintf('https://%s:%s@github.com/%s', $this->githubUser, $this->githubPass, $repo);
    $this->log('Cloning repository');
    $clone_result = $this->execCommand('git clone --depth=1 ' . $url . ' ' . $this->tmpDir, FALSE, 120);
    if ($clone_result) {
      // We had a problem.
      throw new GitCloneException('Problem with the execCommand git clone. Exit code was ' . $clone_result);
    }
    $this->log('Repository cloned');
    if (!$this->chdir($this->tmpDir)) {
      throw new ChdirException('Problem with changing dir to the clone dir.');
    }
    $composer_file = $this->tmpDir . '/composer.json';
    if (!file_exists($composer_file)) {
      throw new \InvalidArgumentException('No composer.json file found.');
    }
    if (!$cdata = json_decode(file_get_contents($composer_file))) {
      throw new \InvalidArgumentException('Invalid composer.json file');
    }
    $lock_file = $this->tmpDir . '/composer.lock';
    $lock_file_contents = FALSE;
    if (@file_exists($lock_file)) {
      // We might want to know whats in here.
      $lock_file_contents = json_decode(file_get_contents($lock_file));
    }
    $this->app = new Application();
    $app = $this->app;
    $d = $app->getDefinition();
    $opts = $d->getOptions();
    $opts['no-ansi'] = new InputOption('no-ansi', NULL, 4, TRUE, 'Disable ANSI output');
    $d->setOptions($opts);
    $app->setDefinition($d);
    $app->setAutoExit(FALSE);
    $this->doComposerInstall();
    $i = new ArrayInput([
      'show',
      '--latest' => TRUE,
      '-d' => $this->getCwd(),
      '--direct' => TRUE,
      '--minor-only' => TRUE,
      '--format' => 'json',
    ]);
    $b = new ArrayOutput();
    $app->run($i, $b);
    $raw_data = $b->fetch();
    foreach ($raw_data as $delta => $item) {
      if (empty($item) || empty($item[0])) {
        continue;
      }
      if (!$json_update = @json_decode($item[0])) {
        // Not interesting.
        continue;
      }
      $data[] = $json_update;
    }
    if (empty($data)) {
      $this->cleanup();
      return;
    }
    $client = new Client(new Builder(), 'polaris-preview');
    $client->authenticate($this->token, NULL, Client::AUTH_URL_TOKEN);
    // Get the default branch of the repo.
    $private_client = new Client();
    $private_client->authenticate($this->githubUser, NULL, Client::AUTH_HTTP_TOKEN);
    $repo = $private_client->api('repo')->show($user_name, $user_repo);
    $private = FALSE;
    if ($repo['private']) {
      $private = TRUE;
    }
    $default_branch = $repo['default_branch'];
    // Try to see if we have already dealt with this (i.e already have a branch
    // for all the updates.
    $pr_client = $client;
    $branch_user = $this->forkUser;
    if ($private) {
      $pr_client = $private_client;
      $branch_user = $user_name;
    }
    $branches_flattened = [];
    $prs_named = [];
    $default_base = NULL;
    try {
      $branches = $pr_client->api('repo')->branches($branch_user, $user_repo);
      $branches_upstream = $private_client->api('repo')->branches($user_name, $user_repo);
      $prs = $private_client->api('pr')->all($user_name, $user_repo);

      foreach ($branches as $branch) {
        $branches_flattened[] = $branch['name'];
        if ($branch['name'] == $default_branch) {
          $default_base = $branch['commit']['sha'];
        }
      }

      foreach ($branches_upstream as $branch) {
        if ($branch['name'] == $default_branch) {
          $default_base = $branch['commit']['sha'];
        }
      }

      foreach ($prs as $pr) {
        $prs_named[$pr['head']['ref']] = $pr;
      }
    }
    catch (RuntimeException $e) {
      // Safe to ignore.
    }
    foreach ($data as $delta => $item) {
      $branch_name = $this->createBranchName($item);
      if (in_array($branch_name, $branches_flattened)) {
        // Is there a PR for this?
        if (array_key_exists($branch_name, $prs_named)) {
          if (!$default_base) {
            unset($data[$delta]);
          }
          // Is the pr up to date?
          if ($prs_named[$branch_name]['base']['sha'] == $default_base) {
            unset($data[$delta]);
          }
        }
      }
    }
    if (empty($data)) {
      $this->log('No updates that have not already been pushed.');
      $this->cleanup();
      return;
    }

    // Unshallow the repo, for syncing it.
    $this->execCommand('git pull --unshallow', FALSE, 300);
    // If the repo is private, we need to push directly to the repo.
    if (!$private) {
      $fork = $client->api('repo')->forks()->create($user_name, $user_repo, [
        'organization' => $this->forkUser,
      ]);
      $fork_url = sprintf('https://%s:%s@github.com/%s/%s', $this->githubUserName, $this->githubUserPass, $this->forkUser, $user_repo);
      $this->execCommand('git remote add fork ' . $fork_url, FALSE);
      // Sync the fork.
      $this->execCommand('git push fork ' . $default_branch);
    }
    // Now read the lockfile.
    $lockdata = json_decode(file_get_contents($this->tmpDir . '/composer.lock'));
    foreach ($data as $item) {
      try {
        $package_name = $item->installed[0]->name;
        $pre_update_data = $this->getPackageData($package_name, $lockdata);
        $version_from = $item->installed[0]->version;
        $version_to = $item->installed[0]->latest;
        // First see if we can update this at all?
        // @todo: Just logging this for now, but this would be nice to have.
        $this->execCommand(sprintf('composer --no-ansi why-not -t %s:%s', $package_name, $version_to), TRUE, 300);
        // See where this package is.
        $req_command = 'require';
        $lockfile_key = 'require';
        if (!empty($cdata->{'require-dev'}->{$package_name})) {
          $lockfile_key = 'require-dev';
          $req_command = 'require --dev';
          $req_item = $cdata->{'require-dev'}->{$package_name};
        }
        else {
          $req_item = $cdata->{'require'}->{$package_name};
        }
        // Create a new branch.
        $branch_name = $this->createBranchName($item);
        $this->log('Checking out new branch: ' . $branch_name);
        $this->execCommand('git checkout -b ' . $branch_name, FALSE);
        // Make sure we do not have any uncommitted changes.
        $this->execCommand('git checkout .', FALSE);
        // Try to use the same version constraint.
        $version = (string) $req_item;
        switch ($version[0]) {
          case '^':
            $constraint = '^';
            break;

          case '~':
            $constraint = '~';
            break;

          default:
            $constraint = '';
            break;
        }
        if (!$lock_file_contents) {
          $command = sprintf('composer --no-ansi %s %s:%s%s', $req_command, $package_name, $constraint, $version_to);
          $this->execCommand($command, FALSE, 600);
        }
        else {
          $command = 'COMPOSER_DISCARD_CHANGES=true composer --no-ansi update -n --no-scripts --with-dependencies ' . $package_name;
          $this->execCommand($command, FALSE, 600);
          // If the constraint is empty, we also try to require the new version.
          if ($constraint == '' && strpos($version, 'dev') === FALSE) {
            // @todo: Duplication from like 6 lines earlier.
            $command = sprintf('composer --no-ansi %s %s:%s%s --update-with-dependencies', $req_command, $package_name, $constraint, $version_to);
            $this->execCommand($command);
          }
        }
        // Clean away the lock file if we are not supposed to use it. But first
        // read it for use later.
        $new_lockdata = json_decode(file_get_contents($this->tmpDir . '/composer.lock'));
        $post_update_data = $this->getPackageData($package_name, $new_lockdata);
        if (isset($post_update_data->source) || $post_update_data->source->type == 'git') {
          $version_from = $pre_update_data->source->reference;
          $version_to = $post_update_data->source->reference;
        }
        if ($version_to === $version_from) {
          // Nothing has happened here. Although that can be alright (like we
          // have updated some dependencies of this package) this is not what
          // this service does, currently, and also the title of the PR would be
          // wrong.
          throw new NotUpdatedException('The version installed is still the same after trying to update.');
        }
        $this->execCommand('git clean -f composer.*');
        $command = sprintf('GIT_AUTHOR_NAME="%s" GIT_AUTHOR_EMAIL="%s" GIT_COMMITTER_NAME="%s" GIT_COMMITTER_EMAIL="%s" git commit composer.* -m "Update %s"',
          $this->githubUserName,
          $this->githubEmail,
          $this->githubUserName,
          $this->githubEmail,
          $package_name
        );
        if ($this->execCommand($command, FALSE)) {
          throw new \Exception('Error committing the composer files. They are probably not changed.');
        }
        $origin = 'fork';
        if ($private) {
          $origin = 'origin';
        }
        if ($this->execCommand("git push $origin $branch_name --force")) {
          throw new GitPushException('Could not push to ' . $branch_name);
        }
        $this->log('Trying to retrieve changelog for ' . $package_name);
        $changelog = NULL;
        try {
          $changelog = $this->retrieveChangeLog($package_name, $lockdata, $version_from, $version_to);
        }
        catch (\Exception $e) {
          // New feature. Just log it.
          $this->log('Exception for changelog: ' . $e->getMessage());
        }
        $this->log('Creating pull request from ' . $branch_name);
        $head = $this->forkUser . ':' . $branch_name;
        if ($private) {
          $head = $branch_name;
        }
        $body = $this->createBody($item, $changelog);
        $pullRequest = $pr_client->api('pull_request')->create($user_name, $user_repo, array(
          'base'  => $default_branch,
          'head'  => $head,
          'title' => $this->createTitle($item),
          'body'  => $body,
        ));
        if (!empty($pullRequest['html_url'])) {
          $this->messages[] = new Message($pullRequest['html_url'], 'pr');
        }
      }
      catch (ValidationFailedException $e) {
        // @todo: Do some better checking. Could be several things, this.
        $this->log('Had a problem with creating the pull request: ' . $e->getMessage());
      }
      catch (\Exception $e) {
        // @todo: Should probably handle this in some way.
        $this->log('Caught an exception: ' . $e->getMessage());
      }
      $this->log('Checking out default branch - ' . $default_branch);
      $this->execCommand('git checkout ' . $default_branch, FALSE);
    }
    // Clean up.
    $this->cleanUp();
  }

  /**
   * Get the messages that are logged.
   *
   * @return \eiriksm\CosyComposer\Message[]
   *   The logged messages.
   */
  public function getOutput() {
    return $this->messages;
  }

  /**
   * Cleans up after the run.
   */
  private function cleanUp() {
    $this->chdir('/tmp');
    $this->log('Cleaning up after update check.');
    $this->log('Storing custom composer cache for later');
    $this->execCommand(sprintf('rsync -az --exclude "composer.*" %s/* %s', $this->tmpDir, $this->createCacheDir()), FALSE, 300);
    $this->execCommand('rm -rf ' . $this->tmpDir, FALSE, 300);
  }

  /**
   * Returns the cache directory, and creates it if necessary.
   *
   * @return string
   */
  public function createCacheDir() {
    $dir_name = md5($this->slug);
    $path = sprintf('%s/%s', $this->cacheDir, $dir_name);
    if (!file_exists($path)) {
      mkdir($path, 0777, TRUE);
    }
    return $path;
  }

  /**
   * Creates a title for a PR.
   *
   * @param object $item
   *   The item in question.
   *
   * @return string
   *   A string ready to use.
   */
  protected function createTitle($item) {
    $update = new ViolinistUpdate();
    $update->setName($item->installed[0]->name);
    $update->setCurrentVersion($item->installed[0]->version);
    $update->setNewVersion($item->installed[0]->latest);
    return $this->messageFactory->getPullRequestTitle($update);
  }

  /**
   * @param $item
   *
   * @return string
   */
  public function createBody($item, $changelog = NULL) {
    $update = new ViolinistUpdate();
    $update->setName($item->installed[0]->name);
    $update->setCurrentVersion($item->installed[0]->version);
    $update->setNewVersion($item->installed[0]->latest);
    if ($changelog) {
      /** @var \eiriksm\GitLogFormat\ChangeLogData $changelog */
      $update->setChangelog($changelog->getAsMarkdown());
    }
    return $this->messageFactory->getPullRequestBody($update);
  }

  /**
   * @param $item
   *
   * @return mixed
   */
  protected function createBranchName($item) {
    $this->debug('Creating branch name based on ' . print_r($item, TRUE));
    $item_string = sprintf('%s%s%s', $item->installed[0]->name, $item->installed[0]->version, $item->installed[0]->latest);
    // @todo: Fix this properly.
    $result = preg_replace('/[^a-zA-Z0-9]+/', '', $item_string);
    $this->debug('Creating branch named ' . $result);
    return $result;
  }

  /**
   * Executes a command.
   */
  protected function execCommand($command, $log = TRUE, $timeout = 120) {
    if ($log) {
      $this->log("Creating command $command");
    }
    $process = $this->getProcess($command);
    $process->setTimeout($timeout);
    $process->run();
    $stdout = $process->getOutput();
    $this->setLastStdOut($stdout);
    $stderr = $process->getErrorOutput();
    $this->setLastStdErr($stderr);
    if (!empty($stdout) && $log) {
      $this->log("stdout: $stdout");
    }
    if (!empty($stderr) && $log) {
      $this->log("stderr: $stderr");
    }
    $returnCode = $process->getExitCode();
    return $returnCode;
  }

  protected function getProcess($command) {
    if ($process = $this->getProcessForCommand($command)) {
      return $process;
    }
    return new Process($command, $this->getCwd());
  }

  protected function getProcessForCommand($command) {
    if (!empty($this->processesForCommands[$command])) {
      return $this->processesForCommands[$command];
    }
    return FALSE;
  }

  public function setProcessForCommand($command, $process) {
    $this->processesForCommands[$command] = $process;
  }

  /**
   * Sets the function to call for getting the contents.
   *
   * @param $callable
   *   A callable function.
   */
  public function setContentGetter($callable) {
    $this->contentGetter = $callable;
  }

  public function setPipes(array $pipes) {
    $this->pipes = $pipes;
  }

  public function getPipes() {
    return $this->pipes;
  }

  /**
   * @param string $proc_close
   */
  public function setProcClose($proc_close) {
    $this->proc_close = $proc_close;
  }

  /**
   * @param string $proc_open
   */
  public function setProcOpen($proc_open) {
    $this->proc_open = $proc_open;
  }

  protected function debug($message) {
    if ($this->verbose) {
      $this->log($message);
    }
  }

  /**
   * Log a message.
   *
   * @param string $message
   */
  protected function log($message) {
    if ($this->verbose) {
      print_r("$message\n");
    }
    $this->messages[] = new Message($message);
  }

  /**
   * Does a composer install.
   *
   * @throws \eiriksm\CosyComposer\Exceptions\ComposerInstallException
   */
  protected function doComposerInstall() {
    // First copy the custom cache in here.
    if (file_exists($this->createCacheDir())) {
      $this->log('Found custom cache. using this for vendor folder.');
      $this->execCommand(sprintf('rsync -a %s/* %s/', $this->createCacheDir(), $this->tmpDir), FALSE, 300);
    }
    // @todo: Should probably use composer install command programatically.
    $this->log('Running composer install');
    if ($code = $this->execCommand('composer install --no-ansi -n --no-scripts', FALSE, 1200)) {
      // Other status code than 0.
      $this->messages[] = new Message($this->getLastStdOut(), 'stdout');
      $this->messages[] = new Message($this->getLastStdErr(), 'stderr');
      throw new ComposerInstallException('Composer install failed with exit code ' . $code);
    }
    $this->log('composer install completed successfully');
  }

  /**
   * Changes to a different directory.
   */
  private function chdir($dir) {
    if (!file_exists($dir)) {
      return FALSE;
    }
    $this->setCWD($dir);
    return TRUE;
  }

  protected function setCWD($dir) {
    $this->cwd = $dir;
  }

  /**
   * @return string
   */
  public function getTmpDir() {
    return $this->tmpDir;
  }

  /**
   * @param string $tmpDir
   */
  public function setTmpDir($tmpDir) {
    $this->tmpDir = $tmpDir;
  }

  /**
   * @param $package_name
   * @param $lockdata
   */
  public function retrieveChangeLog($package_name, $lockdata, $version_from, $version_to) {
    $data = $this->getPackageData($package_name, $lockdata);
    $clone_path = $this->retrieveDependencyRepo($data);
    // Then try to get the changelog.
    $command = sprintf('git -C %s log %s..%s --oneline', $clone_path, $version_from, $version_to);
    $this->execCommand($command, FALSE);
    $changelog_string = $this->getLastStdOut();
    if (empty($changelog_string)) {
      throw new \Exception('The changelog string was empty');
    }
    // Then split it into lines that makes sense.
    $log = ChangeLogData::createFromString($changelog_string);
    // Then assemble the git source.
    $git_url = preg_replace('/.git$/', '', $data->source->url);
    $log->setGitSource($git_url);
    return $log;
  }

  private function retrieveDependencyRepo($data) {
    // First find the repo source.
    if (!isset($data->source) || $data->source->type != 'git') {
      throw new \Exception('Unknown source or non-git source. Aborting.');
    }
    // We could have this cached in the md5 of the package name.
    $clone_path = '/tmp/' . md5($data->name);
    $repo_path = $data->source->url;
    if (!file_exists($clone_path)) {
      $this->execCommand(sprintf('git clone %s %s', $repo_path, $clone_path), FALSE, 300);
    }
    else {
      $this->execCommand(sprintf('git -C %s pull', $clone_path), FALSE, 300);
    }
    return $clone_path;
  }

  private function getPackageData($package_name, $lockdata) {
    $lockfile_key = 'packages';
    $key = $this->getPackagesKey($package_name, $lockfile_key, $lockdata);
    if ($key === FALSE) {
      // Well, could be a dev req.
      $lockfile_key = 'packages-dev';
      $key = $this->getPackagesKey($package_name, $lockfile_key, $lockdata);
      // If the key still is false, then this is not looking so good.
      if ($key === FALSE) {
        throw new \Exception(sprintf('Did not find the requested package (%s) in the lockfile. This is probably an error', $package_name));
      }
    }
    return $lockdata->{$lockfile_key}[$key];
  }

  private function getPackagesKey($package_name, $lockfile_key, $lockdata) {
    $names = array_column($lockdata->{$lockfile_key}, 'name');
    return array_search($package_name, $names);
  }
}
