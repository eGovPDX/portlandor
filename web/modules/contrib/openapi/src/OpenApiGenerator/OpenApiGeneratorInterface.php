<?php

namespace Drupal\openapi\OpenApiGenerator;

/**
 * Generates OpenAPI Spec.
 *
 * @todo Is this interface needed? Could this just contain getSpecification()?
 *   The move all the functions to abstract functions OpenApiGeneratorBase.
 */
interface OpenApiGeneratorInterface {

  /**
   * @return string
   */
  public function getBasePath();

  /**
   * @return array
   */
  public function getSecurityDefinitions();

  /**
   *
   * @param array $options
   *   The options for generating the schema.
   *
   * @return array
   */
  public function getTags(array $options = []);

  /**
   * Returns the paths information.
   *
   * @param array $options
   *   The options for generating the schema.
   *
   * @return array
   *   The info elements.
   */
  public function getPaths(array $options = []);

  /**
   * Generates OpenAPI specification.
   *
   * @param array $options
   *   The options for the specification generation.
   *   - exclude: Array of Entity types or bundles to exclude in the format,
   *      "[ENTITY_TYPE]" or "[ENTITY_TYPE]:[BUNDLE]".
   *
   * @todo Document all options.
   *
   * @return array
   *   The specification output.
   */
  public function getSpecification(array $options = []);


  /**
   * Get model definitions for Drupal entities and bundles.
   *
   * @param array $options
   *   The options for the specification generation.
   *
   * @return array
   *   The model definitions.
   */
  public function getDefinitions(array $options = []);

}
