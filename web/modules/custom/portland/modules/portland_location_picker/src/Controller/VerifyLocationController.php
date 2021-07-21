<?php
namespace Drupal\portland_location_picker\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class VerifyLocationController
 * @package Drupal\mymodule\Controller
 */
class VerifyLocationController {

  /**
   * @return JsonResponse
   */
  public function GetMatches($address) {
    $results = $this->getAddressMatches($address);
    return new JsonResponse([ 'data' => $results]);
  }

  // /**
  //  * @return JsonResponse
  //  */
  // public function GetMatches(&$form, $form_state, $form_id) {
  //   $address = $form_state->getValue('portland_location_picker')['location_address'];
  //   $results = $this->getAddressMatches($address);
  //   return new JsonResponse([ 'data' => $results]);
  // }

  /**
   * @return array
   */
  public function getAddressMatches($address) {

    $result = ['test' => $address];

    return json_encode($result);

    // portland_address_complete calls the portlandmaps api that we need; copy from there
  }
}