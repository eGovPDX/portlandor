<?php

namespace Drupal\portland_zendesk\Client;
use Zendesk\API\HttpClient;

/**
 * Class ZendeskClient
 * @package Drupal\portland_zendesk\Client
 */
class ZendeskClient extends HttpClient
{

    /**
     * ZendeskClient constructor.
     * @param string $scheme
     * @param string $hostname
     * @param int $port
     * @param \GuzzleHttp\Client|null $guzzle
     * @throws \Zendesk\API\Exceptions\AuthException
     */
    public function __construct($scheme = "https", $hostname = "zendesk.com", $port = 443, \GuzzleHttp\Client $guzzle = null)
    {
        $config = \Drupal::config('portland_zendesk.adminsettings');

        $subdomain = $config->get('subdomain');
        $username = $config->get('user_email');
        $token = $config->get('web_token');

        parent::__construct($subdomain, $username, $scheme, $hostname, $port, $guzzle);

        $this->setAuth(
            'basic',
            [
                'username' => $username,
                'token' => $token
            ]
        );
    }
}