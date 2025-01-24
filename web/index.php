<?php

/**
 * @file
 * The PHP page that serves all page requests on a Drupal installation.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';

$kernel = new DrupalKernel('prod', $autoloader);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);

// Hack to allow Auth0 to work with Drupal.
if (str_contains($request->getPathInfo(), '/auth0')) {
  require('auth0/index.php');
  exit;
}

$response->send();

$kernel->terminate($request, $response);
