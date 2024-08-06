<?php

namespace Drupal\portland_legacy_redirects\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
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
  protected function computeValue() {
    $entity = $this->getEntity();
    $nid = $entity->Id();
    $type = $entity->getEntityTypeId();
    $content_lang = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    if ($nid && $type) {
      $redirects = \Drupal::service('redirect.repository')->findByDestinationUri(["internal:/$type/$nid", "entity:$type/$nid"]);
      $delta = 0;
      foreach ($redirects as $key => $redirect) {
        if ($redirect->language->value !== $content_lang) continue;
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

      // retrieve all existing redirects for this content
      $type = $entity->getEntityTypeId();
      $nid = $entity->id();
      $content_lang = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      // get all existing redirects in current content language
      $existing_redirects = array_filter(
        \Drupal::service('redirect.repository')->findByDestinationUri(["internal:/$type/$nid", "entity:$type/$nid"]),
        fn($r) => $r->language->value === $content_lang,
      );
      // turn field_redirects into an array of paths, removing starting and trailing slash
      $new_redirect_paths = array_map(fn($r) => trim($r['value'], '/'), $entity->get('field_redirects')->getValue());
      foreach ($existing_redirects as $redirect) {
        // delete any redirects that aren't in the new redirect field value
        if (!in_array($redirect->getSource()['path'], $new_redirect_paths)) $redirect->delete();
      }

      $existing_redirect_paths = array_map(fn($r) => $r->getSource()['path'], $existing_redirects);
      foreach ($new_redirect_paths as $redirect_source) {
        // create any redirects from the new field value that don't exist in the DB
        if (!in_array($redirect_source, $existing_redirect_paths)) {
          $redirect_redirect = "entity:$type/" . $nid;
          Redirect::create([
            'redirect_source' => $redirect_source,
            'redirect_redirect' => $redirect_redirect,
            'language' => $content_lang,
            'status_code' => 301,
          ])->save();
        }
      }
    }
    return parent::postSave($update);
  }

}
