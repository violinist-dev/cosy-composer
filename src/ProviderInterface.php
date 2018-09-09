<?php

namespace eiriksm\CosyComposer;

interface ProviderInterface
{
    public function authenticate($user, $token);

    public function authenticatePrivate($user, $token);

    public function repoIsPrivate($user, $repo);

    public function getDefaultBranch($user, $repo);

    public function getBranchesFlattened($user, $repo);

    public function getPrsNamed($user, $repo);

    public function getDefaultBase($user, $repo, $default_branch);

    public function createFork($user, $repo, $fork_user);

    /**
     * @param $user_name
     * @param $user_repo
     * @param $params
     *   An array that consists of the following:
     *   - base (a base branch).
     *   - head (I think the branch name to pull in?)
     *   - title (PR title)
     *   - body (PR body)
     *
     * @return mixed
     */
    public function createPullRequest($user_name, $user_repo, $params);

    public function updatePullRequest($user_name, $user_repo, $id, $params);
}
