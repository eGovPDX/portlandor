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
      // $replace = $processed_element['remove_button'];
      // $replace['#name'] = "file_replace_button";
      // $replace['#value'] = t("Replace");
      // $replace['#weight'] = 2;
      // $processed_element['replace_button'] = $replace;

      $fid = $element['#default_value']['fids'][0];
      $file = \Drupal\file\Entity\File::load($fid);
      // Only show replace link on permanent files
      if( $file->isPermanent() ) {
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
