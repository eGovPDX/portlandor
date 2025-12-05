<?php

declare(strict_types=1);

namespace Drupal\portland_webforms;

use Drupal\path_alias\AliasManagerInterface;
use Drupal\redirect\RedirectRepository;
use Drupal\webform\WebformInterface;

/**
 * Helper class to find node IDs referenced by portland_node_fetcher elements.
 *
 * This service traverses a webform's element tree and extracts all node IDs
 * that are configured in portland_node_fetcher elements, resolving path
 * aliases and redirects in the same way the element plugin does.
 */
final class NodeFetcherUsageHelper {

  /**
   * Constructs a NodeFetcherUsageHelper object.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   The path alias manager service.
   * @param \Drupal\redirect\RedirectRepository|null $redirectRepository
   *   The redirect repository service, if the redirect module is installed.
   */
  public function __construct(
    private readonly AliasManagerInterface $aliasManager,
    private readonly ?RedirectRepository $redirectRepository = NULL,
  ) {}

  /**
   * Gets all target node IDs from portland_node_fetcher elements in a webform.
   *
   * This method traverses the webform's element tree, finds all elements with
   * type 'portland_node_fetcher', and resolves their configured paths to node
   * IDs using the same logic as the PortlandNodeFetcher element plugin.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform entity to analyze.
   *
   * @return int[]
   *   An array of unique node IDs (as integers) referenced by
   *   portland_node_fetcher elements. Returns an empty array if no such
   *   elements are found or if none resolve to valid nodes.
   */
  public function getTargetNodeIdsFromWebform(WebformInterface $webform): array {
    $elements = $webform->getElementsDecoded();
    
    if (empty($elements) || !is_array($elements)) {
      return [];
    }

    $node_ids = [];
    $this->traverseElements($elements, $node_ids);

    // Return unique node IDs sorted numerically.
    $node_ids = array_unique($node_ids);
    sort($node_ids, SORT_NUMERIC);
    
    return $node_ids;
  }

  /**
   * Gets node IDs with their element keys from portland_node_fetcher elements.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform entity to analyze.
   *
   * @return array
   *   An associative array keyed by node ID, where each value is an array of
   *   element machine names that reference that node. Example:
   *   [123 => ['about_content', 'footer_content'], 456 => ['header_block']]
   */
  public function getTargetNodeIdsWithElementKeys(WebformInterface $webform): array {
    $elements = $webform->getElementsDecoded();
    
    if (empty($elements) || !is_array($elements)) {
      return [];
    }

    $node_element_map = [];
    $this->traverseElementsWithKeys($elements, $node_element_map);
    
    return $node_element_map;
  }

  /**
   * Recursively traverses webform elements to find portland_node_fetcher types.
   *
   * @param array $elements
   *   The elements array or sub-array to traverse.
   * @param int[] $node_ids
   *   Array passed by reference to collect found node IDs.
   */
  private function traverseElements(array $elements, array &$node_ids): void {
    foreach ($elements as $key => $element) {
      // Skip non-array elements and keys starting with '#' (properties).
      if (!is_array($element) || str_starts_with((string) $key, '#')) {
        continue;
      }

      // Check if this is a portland_node_fetcher element.
      if (isset($element['#type']) && $element['#type'] === 'portland_node_fetcher') {
        $nid = $this->resolveNodeIdFromElement($element);
        if ($nid !== NULL) {
          $node_ids[] = $nid;
        }
      }

      // Recursively traverse child elements.
      $this->traverseElements($element, $node_ids);
    }
  }

  /**
   * Recursively traverses elements, collecting node IDs with element keys.
   *
   * @param array $elements
   *   The elements array or sub-array to traverse.
   * @param array $node_element_map
   *   Array passed by reference, keyed by node ID with element keys as values.
   */
  private function traverseElementsWithKeys(array $elements, array &$node_element_map): void {
    foreach ($elements as $key => $element) {
      // Skip non-array elements and keys starting with '#' (properties).
      if (!is_array($element) || str_starts_with((string) $key, '#')) {
        continue;
      }

      // Check if this is a portland_node_fetcher element.
      if (isset($element['#type']) && $element['#type'] === 'portland_node_fetcher') {
        $nid = $this->resolveNodeIdFromElement($element);
        if ($nid !== NULL) {
          // Initialize array for this node if not exists.
          if (!isset($node_element_map[$nid])) {
            $node_element_map[$nid] = [];
          }
          // Add this element's machine name only if not already present (prevent duplicates).
          if (!in_array($key, $node_element_map[$nid], TRUE)) {
            $node_element_map[$nid][] = $key;
          }
        }
        // Don't recurse into portland_node_fetcher elements - they're leaf nodes.
        continue;
      }

      // Recursively traverse child elements.
      $this->traverseElementsWithKeys($element, $node_element_map);
    }
  }

  /**
   * Resolves a node ID from a portland_node_fetcher element configuration.
   *
   * This method replicates the path resolution logic from the
   * PortlandNodeFetcher::prepare() method, including redirect resolution
   * and alias-to-path conversion.
   *
   * @param array $element
   *   The element configuration array.
   *
   * @return int|null
   *   The resolved node ID, or NULL if the path doesn't resolve to a node.
   */
  private function resolveNodeIdFromElement(array $element): ?int {
    $alias = $element['#node_alias_path'] ?? '';
    
    if (empty($alias)) {
      return NULL;
    }

    // Resolve redirect if one exists for this alias.
    $resolved_path = $this->resolveRedirect($alias);

    // Convert alias to system path (e.g., /about-us -> /node/123).
    $system_path = $this->aliasManager->getPathByAlias($resolved_path);

    // Extract node ID from /node/{nid} pattern.
    if (preg_match('/^\/node\/(\d+)$/', $system_path, $matches)) {
      return (int) $matches[1];
    }

    return NULL;
  }

  /**
   * Resolves a redirect for the given alias, if one exists.
   *
   * This replicates the redirect resolution logic from PortlandNodeFetcher.
   *
   * @param string $alias
   *   The path alias to check for redirects.
   *
   * @return string
   *   The resolved path after following redirects, or the original alias
   *   if no redirect exists or the redirect module is not available.
   */
  private function resolveRedirect(string $alias): string {
    // If redirect module is not available, return the original alias.
    if ($this->redirectRepository === NULL) {
      return $alias;
    }

    // Remove leading slash for redirect.repository lookup.
    $alias_trimmed = ltrim($alias, '/');
    $redirects = $this->redirectRepository->findBySourcePath($alias_trimmed);
    
    if (empty($redirects)) {
      return $alias;
    }

    $redirect = reset($redirects);
    if ($redirect && method_exists($redirect, 'getRedirect')) {
      $redirect_url = $redirect->getRedirect();
      
      // Handle internal:/node/12345 and similar URIs.
      if (is_array($redirect_url) && isset($redirect_url['uri'])) {
        $uri = $redirect_url['uri'];
        if (str_starts_with($uri, 'internal:/')) {
          // Convert internal:/node/12345 to /node/12345.
          return substr($uri, strlen('internal:'));
        }
        // For other URI schemes (external:, route:), return as-is.
        return $uri;
      }
    }

    return $alias;
  }

}
