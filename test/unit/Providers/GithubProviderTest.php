<?php

namespace eiriksm\CosyComposerTest\unit\Providers;

use eiriksm\CosyComposer\Providers\Github;
use Github\Client;

class GithubProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testAuthenticate()
    {
        $mock_client = $this->getMockClient();
        $user = 'testUser';
        $password = 'testPassword';
        $mock_client->expects($this->once())
            ->method('authenticate')
            ->with($user, null, Client::AUTH_URL_TOKEN);
        $g = new Github($mock_client);
        $g->authenticate($user, $password);
    }

    public function testAuthenticatePrivate()
    {
        $mock_client = $this->getMockClient();
        $user = 'testUser';
        $password = 'testPassword';
        $mock_client->expects($this->once())
            ->method('authenticate')
            ->with($user, null, Client::AUTH_HTTP_TOKEN);
        $g = new Github($mock_client);
        $g->authenticatePrivate($user, $password);
    }

    private function getMockClient()
    {
        return $this->createMock(Client::class);
    }
}
