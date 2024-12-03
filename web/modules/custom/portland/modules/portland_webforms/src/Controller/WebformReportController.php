<?php

namespace Drupal\portland_webforms\Controller;

use Drupal\webform\Entity\Webform;
use Symfony\Component\HttpFoundation\Response;

class WebformReportController
{

  public function generateCsv() {
    $webforms = Webform::loadMultiple();
    $rows = [];

    foreach ($webforms as $webform) {
        $elements = $webform->getElementsDecoded(); // Decode the elements.
        $flattened_elements = $this->flattenElements($elements);

        // Initialize the custom field value.
        $zendesk_custom_field = '';

        // Iterate through all handlers.
        $handlers = $webform->getHandlers();
        foreach ($handlers as $handler) {
            if ($handler->getPluginDefinition()['id'] === 'zendesk') { // Match the handler type.
                $handler_settings = $handler->getConfiguration()['settings'];

                if (!empty($handler_settings['custom_fields'])) {
                    try {
                        $custom_fields = \Symfony\Component\Yaml\Yaml::parse($handler_settings['custom_fields']);
                        if (isset($custom_fields['6353388345367'])) {
                            $zendesk_custom_field = $custom_fields['6353388345367'];
                        }
                    } catch (\Exception $e) {
                        \Drupal::logger('webform_report')->error('Error parsing YAML for webform @id: @error', [
                            '@id' => $webform->id(),
                            '@error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Add a row for each flattened element.
        foreach ($flattened_elements as $key => $element) {
            $rows[] = [
                'Webform Machine Name' => $webform->id(),
                'Webform Title' => $webform->label(),
                'Element Title' => $element['#title'] ?? 'No title',
                'Element Key' => $key,
                'Element Type' => $element['#type'] ?? 'Unknown',
                'Required' => !empty($element['#required']) ? 'Required' : '',
                'Webform ID in Zendesk' => $zendesk_custom_field,
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
    fputcsv($output, ['Webform Machine Name', 'Webform Title', 'Element Title', 'Element Key', 'Element Type', 'Required', 'Webform ID in Zendesk']);

    // Add rows.
    foreach ($rows as $row) {
      fputcsv($output, $row);
    }

    rewind($output);
    $csvContent = stream_get_contents($output);
    fclose($output);

    return $csvContent;
  }
}
