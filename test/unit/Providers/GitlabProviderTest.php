<?php

namespace eiriksm\CosyComposerTest\unit\Providers;

use eiriksm\CosyComposer\Providers\Gitlab;
use Gitlab\Client;

class GitlabProviderTest extends ProvidersTestBase
{
    protected $authenticateArguments = [
        'testUser',
        Client::AUTH_OAUTH_TOKEN
    ];

    protected $authenticatePrivateArguments = [
        'testUser',
        Client::AUTH_OAUTH_TOKEN
    ];

    public function testRepoIsPrivate()
    {
        $client = $this->getMockClient();
        $provider = $this->getProvider($client);
        $this->assertEquals(true, $provider->repoIsPrivate('testUser', 'testRepo'));
    }

    protected function getProvider(Client $client)
    {
        return new Gitlab($client);
    }

    protected function getMockClient()
    {
        return $this->createMock(Client::class);
    }
}
