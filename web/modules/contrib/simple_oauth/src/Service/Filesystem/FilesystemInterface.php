<?php

namespace Drupal\simple_oauth\Service\Filesystem;

use Drupal\Core\File\FileSystemInterface as CoreFileSystemInterface;

/**
 * @internal
 */
interface FilesystemInterface extends CoreFileSystemInterface {

  /**
   * Return true if {@var $extension} is enabled in PHP, else return false.
   *
   * @param string $extension
   *   Extension name.
   *
   * @return bool
   */
  public function isExtensionEnabled($extension);

  /**
   * Return true if {@var $uri} is a directory, else return false.
   *
   * @param string $uri
   *   URI.
   *
   * @return bool
   */
  public function isDirectory($uri);

  /**
   * Return true if {@var $uri} is a writable, else return false.
   *
   * @param string $uri
   *   URI.
   *
   * @return bool
   */
  public function isWritable($uri);

  /**
   * Return true if {@var $uri} is a readable, else return false.
   *
   * @param string $uri
   *   URI.
   *
   * @return bool
   */
  public function isReadable($uri);

  /**
   * Return true if {@var $uri} is a file and it exist , else return false.
   *
   * @param string $uri
   *   URI.
   *
   * @return bool
   */
  public function fileExist($uri);

  /**
   * Write {@var $content} into {@var $uri}. Return true if successful,
   * else return false.
   *
   * @param string $uri
   *   URI.
   * @param string $content
   *   String content that is going to be saved.
   *
   * @return bool
   */
  public function write($uri, $content);

}
