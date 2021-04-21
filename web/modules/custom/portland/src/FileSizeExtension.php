<?php

namespace Drupal\portland;

/**
 * Class FileSizeExtension.
 */
class FileSizeExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   * Let Drupal know the name of your extension
   * must be unique name, string
   */
  public function getName()
  {
    return 'portland.filesizeextension';
  }
 
  /**
   * In this function we can declare the extension function.
   */
  public function getFunctions()
  {
    return array(
      new \Twig_SimpleFunction('file_size_string', array($this, 'file_size_string'), array('is_safe' => array('html'))),
    );
  }

  /**
   * This function is used to build a friendly file size string from raw byte count
   */
  public function file_size_string($bytes) {
    if( empty($bytes) ) return '';
    $kbytes = round($bytes / 1024, 2);
    $mbytes = round($kbytes / 1024, 2);
    $size = "";
    if ($mbytes > 1) {
      $size = $mbytes . ' Mb';
    } else if ($kbytes > 1) {
      $size = $kbytes . ' Kb';
    } else {
      $size = $bytes . ' bytes';
    }
    $size = '(' . $size . ')';
    if ($bytes) {
      return $size;
    } else {
      return '';
    }
  }

}