<?php

namespace eiriksm\CosyComposerTest\unit\Providers;

interface TestProviderInterface
{
    public function getMockClient();

    public function getProvider($client);

    public function getBranchMethod();
}
