<?php

namespace Drupal\portland_pingdom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PingdomController extends ControllerBase {
  public function displayPage(Request $request) {
    // While this code block *should* work, the cache-control header is getting overridden by 
    // something else (Pantheon Advanced Cache module?)
    //
    // $markup = date("F j, Y, g:i:s a");
    // $render_array = [
    //   'pingdom_page' => [
    //     '#markup' => $markup,
    //     '#attached' => [
    //       'http_header' => [
    //         ['Cache-Control', 'no-store, no-cache, max-age=0'],
    //       ]
    //     ]
    //   ]
    // ];
    // return $render_array;


    $date = date("F j, Y, g:i:s a");
    $content = <<<END
<html>
  <head>
    <title>Pingdom Uptime Monitor</title>
  </head>
  <body>
    <p>OK $date</p>
    <p>This monitor verifies that basic Drupal functionality, including the PHP webserver and the database, are all operational.</p>
  </body>
</html>
END;
    $response = new Response();
    $response->headers->set('Cache-Control', 'no-store, no-cache, max-age=0');
    $response->setContent($content);
    return $response;
  }
}
