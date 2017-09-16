<?php

namespace SocialiteProviders\Wework;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'WEWORK';

    /**
     * {@inheritdoc}
     */
    protected $agentId;
    protected $scopes = [];

    /**
     * {@inheritdoc}
     */
    public function setAgentId($agentId)
    {
        $this->agentId = $agentId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        if (!empty($this->scopes)) {
            return $this->getOAuthUrl($state);
        }
        return $this->getQrConnectUrl($state);
    }

    protected function getOAuthUrl($state)
    {
        $queries = [
            'appid' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code',
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'agentid' => $this->agentId,
            'state' => $state,
        ];
        return sprintf('https://open.weixin.qq.com/connect/oauth2/authorize?%s#wechat_redirect', http_build_query($queries));
    }

    protected function getQrConnectUrl($state)
    {
        $queries = [
            'appid' => $this->clientId,
            'agentid' => $this->agentId,
            'redirect_uri' => $this->redirectUrl,
            'state' => $state,
        ];
        return 'https://open.work.weixin.qq.com/wwopen/sso/qrConnect?'.http_build_query($queries);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://qyapi.weixin.qq.com/cgi-bin/gettoken';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $userInfo = $this->getUserInfo($token);
        return $this->getUserDetail($token, $userInfo['UserId']);
    }

    protected function getUserInfo($token)
    {
        $response = $this->getHttpClient()->get('https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo', [
            'query' => array_filter([
                'access_token' => $token,
                'code' => $this->getCode(),
            ]),
        ]);
        return json_decode($response->getBody(), true);
    }

    protected function getUserDetail($token, $userId)
    {
        $response = $this->getHttpClient()->post('https://qyapi.weixin.qq.com/cgi-bin/user/get', [
            'query' => [
                'access_token' => $token,
                'userid' => $userId
            ]
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['userid'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['avatar'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'corpid' => $this->clientId,
            'corpsecret' => $this->clientSecret
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'query' => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);

        return $this->credentialsResponseBody;
    }
}
