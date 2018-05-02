[![Build Status](https://travis-ci.org/e0ipso/simple_oauth.svg?branch=8.x-2.x)](https://travis-ci.org/e0ipso/simple_oauth)

Simple OAuth is an implementation of the [OAuth 2.0 Authorization Framework RFC](https://tools.ietf.org/html/rfc6749). Using OAuth 2.0 Bearer Token is very easy. See how you can get the basics working in **less than 5 minutes**! This project is focused in simplicity of use and flexibility. When deciding which project to use, also consider other projects like [OAuth](https://www.drupal.org/project/oauth), an OAuth 1 implementation that doesn't rely on you having https in your production server.

### Based on League\OAuth2
This module uses the fantastic PHP library [OAuth 2.0 Server](http://oauth2.thephpleague.com) from [The League of Extraordinary Packages](http://thephpleague.com). This library has become the de-facto standard for modern PHP applications and is thoroughly tested.

[![Latest Version](http://img.shields.io/packagist/v/league/oauth2-server.svg?style=flat-square)](https://github.com/thephpleague/oauth2-server/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/thephpleague/oauth2-server/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/oauth2-server)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/thephpleague/oauth2-server.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/oauth2-server/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/thephpleague/oauth2-server.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/oauth2-server)
[![Total Downloads](https://img.shields.io/packagist/dt/league/oauth2-server.svg?style=flat-square)](https://packagist.org/packages/league/oauth2-server)

### Quick demo (Password Grant)

1. Install the module using Composer: `composer require drupal/simple_oauth:8.x-2.x`. You can use any other installation method, as long as you install the [OAuth2 Server](https://github.com/thephpleague/oauth2-server) composer package.
2. Generate a pair of keys to encrypt the tokens. And store them outside of your document root for security reasons.
```
openssl genrsa -out private.key 2048
openssl rsa -in private.key -pubout > public.key
```
3. Save the path to your keys in: `/admin/config/people/simple_oauth`.
3. Go to [REST UI](https://drupal.org/project/restui) and enable the _oauth2_ authentication in your resource.
4. Create a Client Application by going to: `/admin/config/services/consumer/add`.
5. Create a token with your credentials by making a `POST` request to `/oauth/token`. See [the documentation](http://oauth2.thephpleague.com/authorization-server/resource-owner-password-credentials-grant/) about what fields your request should contain.
6.  (Not shown) Permissions are set to only allow to view nodes via REST with the authenticated user.
7.  Request a node via REST without authentication and watch it fail.
8.  Request a node via REST with the header `Authorization: Bearer {YOUR_TOKEN}` and watch it succeed.

![Simple OAuth animation](https://www.drupal.org/files/project-images/simple_oauth_2.gif)

### Video tutorials

[![](https://www.drupal.org/files/2015-12-10%2009-04-11.png)](https://youtu.be/kohs5MXESXc) Watch a detailed explanation on how to use this module in the video tutorials:

1.  [Basic configuration.](https://youtu.be/kohs5MXESXc)
2.  [Refresh your tokens.](https://youtu.be/E-wUKkQa1OM)
3.  [Add extra security with resources.](https://youtu.be/PR0oBCCSxgE)

### My token has expired!

First, that is a good thing. Tokens are like cash, if you have it you can use it. You don't need to prove that token belongs to you, so don't let anyone steal your token. In order to lower the risk tokens should expire fairly quickly. If your token expires in 120s then it will be only usable during that window.

#### What do I do if my token was expired?

Along with your access token, an authentication token is created. It's called the _refresh token_ . It's a longer lived token, that it's associated to an access token and can be used to create a replica of your expired access token. You can then use that new access token normally. To use your refresh token you will need to make use of the [_Refresh Token Grant_](http://oauth2.thephpleague.com/authorization-server/refresh-token-grant/). That will return a JSON document with the new token and a new refresh token. That URL can only be accessed with your refresh token, even if your access token is still valid.

#### What do I do if my refresh token was also expired, or I don't have a refresh token?

Then you will need to generate a new token from scratch. You can avoid this by refreshing your access token before your refresh token expires. This way you avoid the need to require the user to prove their identity to Drupal to create a new token. Another way to mitigate this is to use longer expiration times in your tokens. This will work, but the the recommendation is to refresh your token in time.

### Recommendation

Check the official documentation on the [Bearer Token Usage](http://tools.ietf.org/html/rfc6750). And **turn on SSL!**.

### Issues and contributions

Issues and development happens in the [Drupal.org issue queue](https://www.drupal.org/project/issues/simple_oauth).
