<?php

namespace Drupal\portland_file_replace\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Bytes;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\file\Element\ManagedFile;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\Xss;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget as CoreFileWidget;
use Drupal\plupload_widget\Plugin\Field\FieldWidget\PluploadWidgetTrait;

use Drupal\plupload_widget\UploadConfiguration;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

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
          '#markup' => '<a target= "_blank" href="/admin/content/files/replace/' . $fid . '"> Replace the original file </a>',
        ];
      }
    }
    return $processed_element;
  }
}
