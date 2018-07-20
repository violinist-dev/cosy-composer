<?php

namespace eiriksm\CosyComposer;

use eiriksm\CosyComposer\Providers\Github;
use Github\Client;
use Violinist\Slug\Slug;

class ProviderFactory
{
    public function createFromHost(Slug $slug)
    {
        $host = $slug->getProvider();
        $provider = null;
        switch ($host) {
            case 'github.com':
                $client = new Client();
                $provider = new Github($client);
                break;

            case 'gitlab.com':
                break;

            default:
                throw new \InvalidArgumentException('No provider found for host ' . $host);
        }
        return $provider;
    }
}
