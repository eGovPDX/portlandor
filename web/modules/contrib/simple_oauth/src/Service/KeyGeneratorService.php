<?php

namespace Drupal\simple_oauth\Service;

use Drupal\simple_oauth\Service\Filesystem\FilesystemInterface;
use Drupal\simple_oauth\Service\Filesystem\FilesystemValidator;

/**
 * @internal
 */
class KeyGeneratorService {

  /**
   * @var \Drupal\simple_oauth\Service\Filesystem\Filesystem
   */
  private $fileSystem;

  /**
   * @var \Drupal\simple_oauth\Service\Filesystem\FilesystemValidator
   */
  private $validator;

  /**
   * CertificateGeneratorService constructor.
   *
   * @param \Drupal\simple_oauth\Service\Filesystem\FilesystemInterface $file_system
   */
  public function __construct(FilesystemInterface $file_system) {
    $this->fileSystem = $file_system;
    $this->validator = new FilesystemValidator($file_system);
  }

  /**
   * Generate both public key and private key on the given paths. If no
   * public path is given, then the private path is going to be use for both
   * keys.
   *
   * @param string $dir_path
   *   Private key path.
   *
   * @return void
   */
  public function generateKeys($dir_path) {
    // Create path array.
    $key_names = ['private', 'public'];

    // Validate.
    $this->validator->validateOpensslExtensionExist('openssl');
    $this->validator->validateAreDirs([$dir_path]);
    $this->validator->validateAreWritable([$dir_path]);

    // Create Keys array.
    $keys = KeyGenerator::generateKeys();

    // Create both keys.
    foreach ($key_names as $name) {
      // Key uri.
      $key_uri = "$dir_path/$name.key";
      // Remove old key.
      $this->fileSystem->unlink($key_uri);
      // Write key content to key file.
      $this->fileSystem->write($key_uri, $keys[$name]);
      // Set correct permission to key file.
      $this->fileSystem->chmod($key_uri, 0600);
    }
  }

}
