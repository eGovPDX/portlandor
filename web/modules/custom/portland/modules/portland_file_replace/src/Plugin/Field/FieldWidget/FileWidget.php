<?php

namespace Drupal\portland_file_replace\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget as CoreFileWidget;
/**
 * @FieldWidget(
 *   id = "portland_file_replace_file_widget",
 *   label = @Translation("Portland File widget"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileWidget extends CoreFileWidget {
  public static function process($element, FormStateInterface $form_state, $form) {
    $processed_element = parent::process($element, $form_state, $form);

    // If there is already a file, add "replace file" link
    if( !empty($element['#default_value']['fids']) ) {
      $fid = $element['#default_value']['fids'][0];
      $file = \Drupal\file\Entity\File::load($fid);
      $user = \Drupal::currentUser();
      // Only show replace link on permanent files. file_replace cannot replace temporary files.
      if( $file != null && $user != null && $file->isPermanent() && $user->hasPermission('replace files') ) {
        $processed_element['replace_link'] = [
          '#type' => 'markup',
          '#markup' => '<a target= "_blank" href="/admin/content/files/replace/' . 
          $fid . '">Replace file and keep the original name</a>',
        ];
      }
    }
    return $processed_element;
  }
}
