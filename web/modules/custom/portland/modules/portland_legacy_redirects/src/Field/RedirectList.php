<?php

namespace Drupal\portland_legacy_redirects\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\redirect\Entity\Redirect;

/**
 * Item list for a computed field that displays the current company.
 *
 */
class RedirectList extends FieldItemList implements FieldItemListInterface {

  use ComputedItemListTrait;

  /**
   * Compute the values.
   */
  protected function computeValue(){
    $entity = $this->getEntity();
    $nid = $entity->Id();
    $type = $entity->getEntityTypeId();
    if ($nid && $type) {
      $redirects = \Drupal::service('redirect.repository')->findByDestinationUri(["internal:/$type/$nid", "entity:$type/$nid"]);
      $delta = 0;
      foreach($redirects as $key => $redirect) {
        $this->list[$delta] = $this->createItem($delta, '/' . $redirect->getSource()['path']);
        $delta += 1;
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * We need to override the presave to avoid gcontent saving without
   * host entity id generated.
   */
  public function preSave() {
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    if ($this->valueComputed) {
      $entity = $this->getEntity();

      // retrieve all existing redirects for this contnet; then delete them
      $type = $entity->getEntityTypeId();
      $nid = $entity->Id();
      $redirects = \Drupal::service('redirect.repository')->findByDestinationUri(["internal:/$type/$nid", "entity:$type/$nid"]);
      foreach($redirects as $key => $redirect) {
        $redirect->delete();
      }

      // recreate new redirects for this content
      $redirect_redirect = "entity:$type/" . $nid;
      $field_redirects = $entity->get('field_redirects');
      foreach ($field_redirects as $delta => $value) {
        $redirect_source = $value->value;
        // remove leading slash from value; we inserted it before displaying in form to be more user friendly
        if (substr($redirect_source, 0, 1) == "/") {
          $redirect_source = substr($redirect_source, 1, strlen($redirect_source));
        }
        Redirect::create([
          'redirect_source' => $redirect_source,
          'redirect_redirect' => $redirect_redirect,
          'language' => 'en',
          'status_code' => 301,
        ])->save();
      }
    }
    return parent::postSave($update);
  }

}