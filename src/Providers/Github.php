<?php

namespace eiriksm\CosyComposer\Providers;

use eiriksm\CosyComposer\ProviderInterface;

class Github implements ProviderInterface {
    public function getOpenPullRequests()
    {
        return [];
    }
}