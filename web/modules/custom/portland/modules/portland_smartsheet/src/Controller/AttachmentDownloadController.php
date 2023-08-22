<?php

namespace Drupal\portland_smartsheet\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\portland_smartsheet\Client\SmartsheetClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AttachmentDownloadController extends ControllerBase {
  public static function getHmac($sheet_id, $attachment_id) {
    $key = \Drupal::service('private_key')->get() . Settings::get('hash_salt');
    return Crypt::hmacBase64("portland_smartsheet:attachment_download:{$sheet_id}:{$attachment_id}", $key);
  }

  public function downloadAttachment($sheet_id, $attachment_id, Request $request) {
    $hash = $request->query->get('hash');
    if (!$hash || $hash !== self::getHmac($sheet_id, $attachment_id)) {
      throw new AccessDeniedHttpException();
    }

    $client = new SmartsheetClient($sheet_id);
    $attachment = $client->getAttachment($attachment_id);

    $response = new Response();
    $response->setMaxAge($attachment->urlExpiresInMillis / 1000);
    $response->setPublic();
    $response->setStatusCode(302);
    $response->headers->set('location', $attachment->url);
    return $response;
  }
}
