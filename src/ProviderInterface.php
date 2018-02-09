<?php

namespace eiriksm\CosyComposer;

interface ProviderInterface
{

    public function __construct();

    public function getOpenPullrequests();
}
