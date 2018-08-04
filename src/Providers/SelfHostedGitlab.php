<?php


namespace eiriksm\CosyComposer\Providers;

use Gitlab\Client;

class SelfHostedGitlab extends Gitlab
{

    /**
     * {@inheritdoc}
     */
    public function __construct(Client $client, array $url)
    {
        $client->setUrl(sprintf('%s://%s:%d', $url['scheme'], $url['host'], $url['port']));
        parent::__construct($client);
    }
}
