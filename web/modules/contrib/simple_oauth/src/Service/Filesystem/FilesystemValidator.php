<?php

namespace Drupal\simple_oauth\Service\Filesystem;

use Drupal\simple_oauth\Service\Exception\FilesystemValidationException;
use Drupal\simple_oauth\Service\Exception\ExtensionNotLoadedException;

/**
 * @internal
 */
class FilesystemValidator {

  /**
   * @var \Drupal\simple_oauth\Service\Filesystem\FilesystemInterface
   */
  private $fileSystem;

  /**
   * FilesystemValidator constructor.
   *
   * @param \Drupal\simple_oauth\Service\Filesystem\FilesystemInterface $file_system
   */
  public function __construct(FilesystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * Validate {@var $ext_name} extension exist.
   *
   * @param string $ext_name
   *   extension name.
   *
   * @throws \Drupal\simple_oauth\Service\Exception\ExtensionNotLoadedException
   */
  public function validateOpensslExtensionExist($ext_name) {
    if (!$this->fileSystem->isExtensionEnabled($ext_name)) {
      throw new ExtensionNotLoadedException(
        strtr('Extension "@ext" is not enabled.', ['@ext' => $ext_name])
      );
    }
  }

  /**
   * Validate that {@var $paths} are directories.
   *
   * @param array $paths
   *   List of URIs.
   *
   * @throws \Drupal\simple_oauth\Service\Exception\FilesystemValidationException
   */
  public function validateAreDirs($paths) {
    foreach ($paths as $path) {
      if (!$this->fileSystem->isDirectory($path)) {
        throw new FilesystemValidationException(
          strtr('Directory "@path" is not a valid directory.', ['@path' => $path])
        );
      }
    }
  }

  /**
   * Validate that {@var $paths} are writable.
   *
   * @param array $paths
   *   List of URIs.
   *
   * @throws \Drupal\simple_oauth\Service\Exception\FilesystemValidationException
   */
  public function validateAreWritable($paths) {
    foreach ($paths as $path) {
      if (!$this->fileSystem->isWritable($path)) {
        throw new FilesystemValidationException(
          strtr('Path "@path" is not writable.', ['@path' => $path])
        );
      }
    }
  }

}
