<?php

namespace Drupal\portland;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class FileIconExtension.
 */
class FileIconExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   * Let Drupal know the name of your extension
   * must be unique name, string
   */
  public function getName()
  {
    return 'portland.fileiconextension';
  }
 
  /**
   * In this function we can declare the extension function.
   */
  public function getFunctions()
  {
    return array(
      new TwigFunction('file_icon_markup', array($this, 'file_icon_markup'), array('is_safe' => array('html'))),
    );
  }

  /**
   * This function is used to return the markup for a FontAwesome file icon.
   * It will return pdf, word, excel, ppt or generic. 
   */
  public function file_icon_markup($mime_type) {
    $icon = "<i class=\"fas fa-file\">&nbsp;</i>";
    if ($mime_type == "application/pdf") {
      $icon = "<i class=\"fas fa-file-pdf\">&nbsp;</i>";
    } else if ($mime_type == "application/msword" or $mime_type == "application/vnd.openxmlformats-officedocument.wordprocessingml.document") {
      $icon = "<i class=\"fas fa-file-word\">&nbsp;</i>";
    } else if ($mime_type == "application/vnd.ms-excel" or $mime_type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") {
      $icon = "<i class=\"fas fa-file-excel\">&nbsp;</i>";
    } else if ($mime_type == "application/vnd.ms-powerpoint" or $mime_type == "application/vnd.openxmlformats-officedocument.presentationml.presentation") {
      $icon = "<i class=\"fas fa-file-powerpoint\">&nbsp;</i>";
    }
    return $icon;
  }

}