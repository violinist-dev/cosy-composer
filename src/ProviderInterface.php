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
}
