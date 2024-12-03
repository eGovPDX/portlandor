<?php

namespace Drupal\portland_webforms\Controller;

use Drupal\webform\Entity\Webform;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

class WebformReportController
{

/**
 * Generates the webform report in CSV format.
 */
public function generateCsv() {
  $webforms = Webform::loadMultiple();
  $rows = [];

  foreach ($webforms as $webform) {
      $elements = $webform->getElementsDecoded(); // Decode the elements.
      $flattened_elements = $this->flattenElements($elements);

      // Find entities referencing this webform.
      $embedded_locations = $this->getEmbeddedLocations($webform->id());

      foreach ($flattened_elements as $key => $element) {
          $rows[] = [
              'Webform ID' => $webform->id(),
              'Webform Title' => $webform->label(),
              'Element Key' => $key,
              'Element Type' => $element['#type'] ?? 'Unknown',
              'Element Title' => $element['#title'] ?? 'No title',
              'Required' => !empty($element['#required']) ? 'Yes' : 'No',
              'Embedded Locations' => implode(', ', $embedded_locations),
          ];
      }
  }

  // Generate CSV content.
  $csvContent = $this->generateCsvContent($rows);

  // Return CSV response.
  return new Response(
      $csvContent,
      200,
      [
          'Content-Type' => 'text/csv',
          'Content-Disposition' => 'attachment; filename="webform_report.csv"',
      ]
  );
}

/**
 * Finds the entities where a webform is embedded.
 *
 * @param string $webform_id
 *   The ID of the webform.
 *
 * @return array
 *   An array of entity titles or labels where the webform is embedded.
 */
protected function getEmbeddedLocations($webform_id) {
  $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
  $query->condition('field_webform', $webform_id); // Replace 'field_webform' with the correct field name if needed.
  $query->condition('type', 'city_service'); // Filter by content type if necessary.
  $query->accessCheck(TRUE); // Ensure access checks are performed.

  $node_ids = $query->execute();

  if (empty($node_ids)) {
      return ['Not embedded'];
  }

  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($node_ids);
  $embedded_locations = [];
  foreach ($nodes as $node) {
      $embedded_locations[] = $node->label();
  }
  return $embedded_locations;
}

/**
   * Flattens a hierarchical array of webform elements into a single-level array.
   *
   * @param array $elements
   *   The hierarchical array of webform elements.
   * @param string $parent_key
   *   The parent key for nested elements (used for building full keys).
   *
   * @return array
   *   A flattened array of webform elements.
   */
  protected function flattenElements(array $elements, $parent_key = '')
  {
    $flattened = [];
    $container_types = [
      'page',
      'container',
      'details',
      'fieldset',
      'flexbox_layout',
      'item',
      'section',
      'table',
      'webform_wizard_page',
      'webform_section',
    ];

    foreach ($elements as $key => $element) {
      // Build the full key for nested elements.
      $full_key = $parent_key ? "{$parent_key}.{$key}" : $key;

      // Include the current element if it has a #type.
      if (isset($element['#type'])) {
        $flattened[$full_key] = $element;
      }

      // Check for children in container-like elements.
      if (isset($element['#type']) && in_array(strtolower($element['#type']), $container_types)) {
        // Process elements in the #children property, if available.
        if (isset($element['#children']) && is_array($element['#children'])) {
          $child_elements = $this->flattenElements($element['#children'], $full_key);
          $flattened = array_merge($flattened, $child_elements);
        }
        // Fallback: Process all sub-elements within the container element.
        foreach ($element as $child_key => $child) {
          if (is_array($child)) {
            $child_elements = $this->flattenElements([$child_key => $child], $full_key);
            $flattened = array_merge($flattened, $child_elements);
          }
        }
      }
    }

    return $flattened;
  }

  /**
   * Converts an array of rows into CSV content.
   *
   * @param array $rows
   *   The array of rows to be converted into CSV.
   *
   * @return string
   *   The CSV as a string.
   */
  protected function generateCsvContent(array $rows)
  {
    $output = fopen('php://temp', 'r+');

    // Add headers.
    fputcsv($output, ['Webform Machine Name', 'Webform Title', 'Element Title', 'Element Key', 'Element Type', 'Required', 'Webform ID in Zendesk', 'Embedded Locations']);

    // Add rows.
    foreach ($rows as $row) {
      fputcsv($output, $row);
    }

    rewind($output);
    $csvContent = stream_get_contents($output);
    fclose($output);

    return $csvContent;
  }

  /**
   * Access check for the webform report route.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object of the current user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public static function access(AccountInterface $account) {
    // Check if the user is authenticated.
    if ($account->isAuthenticated()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
