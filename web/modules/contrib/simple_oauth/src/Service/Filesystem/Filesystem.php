<?php

namespace Drupal\simple_oauth\Service\Filesystem;

use Drupal\Core\File\FileSystem as CoreFileSystem;

/**
 * @internal
 */
class Filesystem implements FilesystemInterface {

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  private $fileSystem;

  /**
   * Filesystem constructor.
   *
   * @param \Drupal\Core\File\FileSystem $file_system
   */
  public function __construct(CoreFileSystem $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function isExtensionEnabled($extension) {
    return @extension_loaded($extension);
  }

  /**
   * {@inheritdoc}
   */
  public function isDirectory($uri) {
    return @is_dir($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function isWritable($uri) {
    return @is_writable($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function fileExist($uri) {
    return @file_exists($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function write($uri, $content) {
    return @file_put_contents($uri, $content);
  }

  /**
   * {@inheritdoc}
   */
  public function isReadable($uri) {
    return @is_readable($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function moveUploadedFile($filename, $uri) {
    return $this->fileSystem->moveUploadedFile($filename, $uri);
  }

  /**
   * {@inheritdoc}
   */
  public function chmod($uri, $mode = NULL) {
    return $this->fileSystem->chmod($uri, $mode);
  }

  /**
   * {@inheritdoc}
   */
  public function unlink($uri, $context = NULL) {
    return $this->fileSystem->unlink($uri, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function realpath($uri) {
    return $this->fileSystem->realpath($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri) {
    return $this->fileSystem->dirname($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function basename($uri, $suffix = NULL) {
    return $this->fileSystem->basename($uri, $suffix);
  }

  /**
   * {@inheritdoc}
   */
  public function mkdir($uri, $mode = NULL, $recursive = FALSE, $context = NULL) {
    return $this->fileSystem->mkdir($uri, $mode, $recursive, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function rmdir($uri, $context = NULL) {
    return $this->fileSystem->rmdir($uri, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function tempnam($directory, $prefix) {
    return $this->fileSystem->tempnam($directory, $prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function uriScheme($uri) {
    return $this->fileSystem->uriScheme($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function validScheme($scheme) {
    return $this->fileSystem->validScheme($scheme);
  }

}
