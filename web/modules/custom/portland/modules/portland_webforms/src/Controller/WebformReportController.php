<?php

namespace Drupal\portland_webforms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class WebformReportController extends ControllerBase {
  private const CACHE_ID = 'portland_webforms:webform_report_cache';

  protected SerializerInterface $serializer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->serializer = $container->get('serializer');

    return $instance;
  }

  /**
   * Generates the webform report in CSV format.
   */
  public function generateCsv() {
    $response = new Response(
      '',
      200,
      [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="webform-report.csv"',
      ]
    );

    // Check if the report is already cached.
    $cache = \Drupal::cache()->get(self::CACHE_ID);
    if ($cache && !empty($cache->data)) {
      $response->setContent($cache->data);
    } else {
      // Generate the report data.
      $webforms = Webform::loadMultiple();
      $rows = [];

      foreach ($webforms as $webform) {
        $elements = $webform->getElementsInitializedFlattenedAndHasValue(); // Decode the elements.

        // Find entities referencing this webform.
        //$embedded_locations = $this->getEmbeddedLocations($webform->id());

        // Extract the custom field value from the webform handler.
        $custom_field_value = $this->getCustomFieldValue($webform, '6353388345367');

        foreach ($elements as $key => $element) {
          $rows[] = [
            'Webform ID' => $webform->id(), // Correct Webform ID
            'Webform Title' => $webform->label(),
            'Element Key' => $key,
            'Element Title' => trim(strip_tags($element['#title'])) ?? '',
            'Element Type' => $element['#type'] ?? 'Unknown',
            'Required' => !empty($element['#required']) ? 'Yes' : 'No',
            'Webform ID in Zendesk' => $custom_field_value, // Custom field value
            //'Embedded Location' => implode(', ', $embedded_locations), // Embedded locations
          ];
        }
      }

      // Generate CSV content.
      $csvContent = $this->serializer->serialize($rows, 'csv');
      $response->setContent($csvContent);
      // Cache the generated CSV content for 1 hour.
      \Drupal::cache()->set(self::CACHE_ID, $csvContent, time() + 3600);
    }

    return $response;
  }

  /**
   * Gets the value of a specific custom field from the webform handler configuration.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform entity.
   * @param string $field_id
   *   The custom field ID to extract.
   *
   * @return string
   *   The value of the custom field, or 'Not configured' if not found.
   */
  protected function getCustomFieldValue(Webform $webform, $field_id) {
    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      $configuration = $handler->getConfiguration();
      if (isset($configuration['settings']['custom_fields'])) {
        $custom_fields = $configuration['settings']['custom_fields'];

        // Safely parse the custom_fields YAML or JSON into an array.
        try {
          $custom_fields_array = \Drupal\Component\Serialization\Yaml::decode($custom_fields);
        } catch (\Exception $e) {
          \Drupal::logger('webform_report')->error('Failed to parse custom fields: @error', ['@error' => $e->getMessage()]);
          continue;
        }

        // Check if the desired field exists.
        if (isset($custom_fields_array[$field_id])) {
          $value = $custom_fields_array[$field_id];

          // Handle single values or arrays.
          if (is_array($value)) {
            return implode(', ', $value); // Convert array to string.
          }
          return $value;
        }
      }
    }

    return '';
  }

  // /**
  //  * Finds the entities where a webform is embedded.
  //  *
  //  * @param string $webform_id
  //  *   The ID of the webform.
  //  *
  //  * @return array
  //  *   An array of entity titles or labels where the webform is embedded.
  //  */
  // protected function getEmbeddedLocations($webform_id) {
  //   $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
  //   $query->condition('field_webform', $webform_id); // Replace 'field_webform' with the correct field name if needed.
  //   $query->condition('type', 'city_service'); // Filter by content type if necessary.
  //   $query->accessCheck(TRUE); // Ensure access checks are performed.

  //   $node_ids = $query->execute();

  //   if (empty($node_ids)) {
  //       return [''];
  //   }

  //   $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($node_ids);
  //   $embedded_locations = [];
  //   foreach ($nodes as $node) {
  //       $embedded_locations[] = $node->label();
  //   }
  //   return $embedded_locations;
  // }
}
