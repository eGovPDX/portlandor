<?php

namespace Drupal\subpathauto;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Whether it is recursive call or not.
   *
   * @var bool
   */
  protected $recursiveCall;

  /**
   * Builds PathProcessor object.
   *
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The path processor.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(InboundPathProcessorInterface $path_processor, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    $this->pathProcessor = $path_processor;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $request_path = $this->getPath($request->getPathInfo());
    // The path won't be processed if the path has been already modified by
    // a path processor (including this one), or if this is a recursive call
    // caused by ::isValidPath.
    if ($request_path !== $path || $this->recursiveCall) {
      return $path;
    }

    $original_path = $path;
    $max_depth = $this->getMaxDepth();
    $subpath = [];
    $i = 0;
    while (($path_array = explode('/', ltrim($path, '/'))) && ($max_depth === 0 || $i < $max_depth)) {
      $i++;
      $subpath[] = array_pop($path_array);
      if (empty($path_array)) {
        break;
      }
      $path = '/' . implode('/', $path_array);
      $processed_path = $this->pathProcessor->processInbound($path, $request);
      if ($processed_path !== $path) {
        $path = $processed_path . '/' . implode('/', array_reverse($subpath));

        // Ensure that the path generated is valid. Call ::isValidPath to
        // trigger path processors one more time to ensure proposed new path is
        // valid. Since this method has generated the path, it should ignore all
        // recursive calls made for this method.
        $valid_path = $this->isValidPath($path);

        // Use generated path if it's valid, otherwise give up and return
        // original path to give other path processors chance to make their
        // modifications for the path.
        if ($valid_path) {
          return $path;
        }
        break;
      }
    }

    return $original_path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleableMetadata = NULL) {
    $original_path = $path;
    $subpath = [];
    $max_depth = $this->getMaxDepth();
    $i = 0;
    while (($path_array = explode('/', ltrim($path, '/'))) && ($max_depth === 0 || $i < $max_depth)) {
      $i++;
      $subpath[] = array_pop($path_array);
      if (empty($path_array)) {
        break;
      }
      $path = '/' . implode('/', $path_array);
      $processed_path = $this->pathProcessor->processOutbound($path, $options, $request);
      if ($processed_path !== $path) {
        $path = $processed_path . '/' . implode('/', array_reverse($subpath));
        return $path;
      }
    }

    return $original_path;
  }

  /**
   * Helper function to handle multilingual paths.
   *
   * @param string $path_info
   *   Path that might contain language prefix.
   *
   * @return string $path info
   *   Path without language prefix.
   */
  protected function getPath($path_info) {
    $language_prefix = '/' . $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId() . '/';

    if (substr($path_info, 0, strlen($language_prefix)) == $language_prefix) {
      $path_info = '/' . substr($path_info, strlen($language_prefix));
    }

    return $path_info;
  }

  /**
   * Tests if path is valid.
   *
   * This method will call all of the path processors (including this one).
   * Sufficient protection against recursive calls is needed.
   *
   * @param string $path
   *   The path to be checked.
   *
   * @return bool
   *   Whether path is valid or not.
   */
  protected function isValidPath($path) {
    $this->recursiveCall = TRUE;
    $is_valid = (bool) $this->getPathValidator()->getUrlIfValidWithoutAccessCheck($path);
    $this->recursiveCall = FALSE;

    return $is_valid;
  }

  /**
   * Gets the path validator.
   *
   * @return \Drupal\Core\Path\PathValidatorInterface
   */
  protected function getPathValidator() {
    if (!$this->pathValidator) {
      $this->setPathValidator(\Drupal::service('path.validator'));
    }

    return $this->pathValidator;
  }

  /**
   * Sets the path validator.
   *
   * The path validator couldn't be injected to this class properly since it
   * would cause circular dependency.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   *
   * @return $this
   */
  public function setPathValidator(PathValidatorInterface $path_validator) {
    $this->pathValidator = $path_validator;

    return $this;
  }

  /**
   * Gets the max depth that subpaths should be scanned through.
   *
   * @return int
   */
  protected function getMaxDepth() {
    return $this->configFactory->get('subpathauto.settings')->get('depth');
  }

}
