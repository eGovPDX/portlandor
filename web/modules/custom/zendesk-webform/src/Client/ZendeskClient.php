<?php
/**
 * Created by PhpStorm.
 * User: Steven
 * Date: 2019-05-18
 * Time: 9:43 AM
 */

namespace Drupal\zendesk_webform\Client;
use Zendesk\API\HttpClient;

/**
 * Class ZendeskClient
 * @package Drupal\zendesk_webform\Client
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
        $config = \Drupal::config('zendesk_webform.adminsettings');

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