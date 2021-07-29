<?php
namespace Drupal\portland_location_picker\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\portland_location_picker\Geocoder\Provider\PortlandMaps;

/**
 * Class VerifyLocationController
 * @package Drupal\mymodule\Controller
 */
class VerifyLocationController {

  /**
   * @return JsonResponse
   */
  public function GetMatches($address) {
    $results = new PortlandMaps();
    $results = $this->getAddressMatches($address);
    return new JsonResponse([ 'data' => $results]);
  }


}