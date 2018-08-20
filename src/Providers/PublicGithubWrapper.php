<?php

namespace eiriksm\CosyComposer\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use violinist\ProjectData\ProjectData;

class PublicGithubWrapper extends Github
{
    /**
     * @var string
     */
    private $userToken;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var ProjectData
     */
    private $project;

    /**
     * @param string $userToken
     */
    public function setUserToken($userToken)
    {
        $this->userToken = $userToken;
    }

    public function setUrlFromTokenUrl($url)
    {
        $parsed_url = parse_url($url);
        $this->baseUrl = sprintf('%s://%s', $parsed_url['scheme'], $parsed_url['host']);
    }

    /**
     * @param ProjectData $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

    public function forceUpdateBranch($branch, $sha)
    {
        $http_client = $this->getHttpClient();
        $url = sprintf('%s/api/github/update_branch?nid=%d&token=%s&branch=%s&new_sha=%s', $this->baseUrl, $this->project->getNid(), $this->userToken, $branch, $sha);
        $request = new Request('GET', $url);
        $resp = $this->getHttpClient()->sendRequest($request);
        if ($resp->getStatusCode() != 200) {
            throw new \Exception('Wrong status code on temp token request (' . $resp->getStatusCode() . ').');
        }
        if (!$json = @json_decode((string) $resp->getBody())) {
            throw new \Exception('No json parsed in the temp token response');
        }
    }

    public function createFork($user, $repo, $fork_user)
    {
        // Send all this data to the website endpoint.
        $http_client = $this->getHttpClient();
        $request = new Request('GET', $this->baseUrl . '/api/github/create_fork?nid=' . $this->project->getNid() . '&token=' . $this->userToken);
        $resp = $this->getHttpClient()->sendRequest($request);
        if ($resp->getStatusCode() != 200) {
            throw new \Exception('Wrong status code on temp token request (' . $resp->getStatusCode() . ').');
        }
        if (!$json = @json_decode((string) $resp->getBody())) {
            throw new \Exception('No json parsed in the temp token response');
        }
    }

    public function createPullRequest($user_name, $user_repo, $params)
    {
        $http_client = $this->getHttpClient();
        $data = array_merge($params, [
            'nid' => $this->project->getNid(),
            'token' => $this->userToken,
            'user_name' => $user_name,
            'user_repo' => $user_repo,
        ]);
        $request = new Request('POST', $this->baseUrl . '/api/github/create_pr');
        $resp = $this->getHttpClient()->sendRequest($request);
        if ($resp->getStatusCode() != 200) {
            throw new \Exception('Wrong status code on temp token request (' . $resp->getStatusCode() . ').');
        }
        if (!$json = @json_decode((string) $resp->getBody())) {
            throw new \Exception('No json parsed in the temp token response');
        }
    }

    protected function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client();
        }
        return $this->httpClient;
    }
}
