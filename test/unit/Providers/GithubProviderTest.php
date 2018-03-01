<?php

namespace eiriksm\CosyComposerTest\unit\Providers;

use eiriksm\CosyComposer\Providers\Github;
use Github\Api\Repo;
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

    public function testRepoIsPrivate()
    {
        $user = 'testUser';
        $repo = 'testRepo';
        $mock_repo_api = $this->createMock(Repo::class);
        $mock_repo_api->expects($this->once())
            ->method('show')
            ->with($user, $repo)
            ->willReturn([
                'private' => true,
            ]);
        $mock_client = $this->getMockClient();
        $mock_client->expects($this->once())
            ->method('api')
            ->with('repo')
            ->willReturn($mock_repo_api);
        $g = new Github($mock_client);
        $this->assertEquals(true, $g->repoIsPrivate($user, $repo));
    }

    public function testRepoIsPublic()
    {
        $user = 'testUser';
        $repo = 'testRepo';
        $mock_repo_api = $this->createMock(Repo::class);
        $mock_repo_api->expects($this->once())
            ->method('show')
            ->with($user, $repo)
            ->willReturn([
                'private' => false,
            ]);
        $mock_client = $this->getMockClient();
        $mock_client->expects($this->once())
            ->method('api')
            ->with('repo')
            ->willReturn($mock_repo_api);
        $g = new Github($mock_client);
        $this->assertEquals(false, $g->repoIsPrivate($user, $repo));
    }

    private function getMockClient()
    {
        return $this->createMock(Client::class);
    }
}
