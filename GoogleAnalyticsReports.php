<?php

namespace Drupal\google_analytics_reports;

use GuzzleHttp\Exception\RequestException;

/**
 * GoogleAnalyticsReports service class.
 *
 * @package Drupal\google_analytics_reports
 */
class GoogleAnalyticsReports {

  /**
   * Uri for listing all GA columns.
   *
   * @var string
   */
  public static $GoogleAnalyticsColumnsDefinitionUrl = 'https://www.googleapis.com/analytics/v3/metadata/ga/columns';

  /**
   * Check updates for new Google Analytics fields.
   *
   * @see https://developers.google.com/analytics/devguides/reporting/metadata/v3/devguide#etag
   */
  public static function checkUpdates() {
    if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE != 'install' &&  MAINTENANCE_MODE != 'update') {
      $etag_old = \Drupal::config('google_analytics_reports.settings')->get('metadata_etag');

      try {
        $response = \Drupal::httpClient()->request('GET', self::$GoogleAnalyticsColumnsDefinitionUrl . '?fields=etag', ['timeout' => 2.0]);
      }
      catch (RequestException $e) {
        \Drupal::logger('google_analytics_reports')->error('Failed to Google Analytics metadata definitions due to "%error".', ['%error' => $e->getMessage()]);
        return;
      }

      if ($response->getStatusCode() == 200) {
        $data = $response->getBody()->getContents();
        if (empty($data)) {
          \Drupal::logger('google_analytics_reports')->error('Failed to Google Analytics Column metadata definitions. Received empty content.');
          return;
        }
        $data = json_decode($data, TRUE);
        if ($etag_old == $data['etag']) {
          drupal_set_message(t('All Google Analytics fields is up to date.'));
        }
        else {
          drupal_set_message(t('New Google Analytics fields has been found. Press "Import fields" button to update Google Analytics fields.'));
        }
      }
      else {
        drupal_set_message(t('An error has occurred: @error.', ['@error' => $response->getStatusCode()]), 'error');
      }
    }
  }

  /**
   * Import Google Analytics fields to database using Metadata API.
   *
   * @see https://developers.google.com/analytics/devguides/reporting/metadata/v3/
   */
  public static function importFields() {
    // if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE != 'install' &&  MAINTENANCE_MODE != 'update') {
      try {
        $response = \Drupal::httpClient()->request('GET', self::$GoogleAnalyticsColumnsDefinitionUrl, ['timeout' => 2.0]);
      }
      catch (RequestException $e) {
        \Drupal::logger('google_analytics_reports')->error('Failed to Google Analytics Column metadata definitions due to "%error".', ['%error' => $e->getMessage()]);
        return;
      }
      if ($response->getStatusCode() == 200) {
        $data = $response->getBody()->getContents();
        if (empty($data)) {
          \Drupal::logger('google_analytics_reports')->error('Failed to Google Analytics Column metadata definitions. Received empty content.');
          return;
        }
        $data = json_decode($data, TRUE);
        // Remove old fields.
        if (\Drupal::database()->schema()->tableExists('google_analytics_reports_fields')) {
          \Drupal::database()->truncate('google_analytics_reports_fields')
            ->execute();
        }
        $google_analytics_reports_settings = \Drupal::config('google_analytics_reports.settings')->get();
        // Save current time as last executed time.
        $google_analytics_reports_settings['metadata_last_time'] = REQUEST_TIME;
        // Save etag identifier. It is used to check updates for the fields.
        // @see https://developers.google.com/analytics/devguides/reporting/metadata/v3/devguide#etag
        if (!empty($data['etag'])) {
          $google_analytics_reports_settings['metadata_etag'] = $data['etag'];
        }

        \Drupal::configFactory()->getEditable('google_analytics_reports.settings')
          ->setData($google_analytics_reports_settings)
          ->save();

        if (!empty($data['items'])) {
          $operations = [];
          foreach ($data['items'] as $item) {
            // Do not import deprecated fields.
            if ($item['attributes']['status'] == 'PUBLIC') {
              $operations[] = [
                [GoogleAnalyticsReports::class, 'saveFields'],
                [$item],
              ];
            }
          }
          $batch = [
            'operations' => $operations,
            'title' => t('Importing Google Analytics fields'),
            'finished' => [GoogleAnalyticsReports::class, 'importFieldsFinished'],
          ];
          batch_set($batch);
        }
      }
      else {
        drupal_set_message(t('There is a error during request to Google Analytics Metadata API: @error', ['@error' => $response->getStatusCode()]), 'error');
      }
    // }
  }

  /**
   * Batch processor.
   *
   * Saves Google Analytics fields from Metadata API to database.
   *
   * @param array $field
   *   Field definition.
   * @param array $context
   *   Context.
   */
  public static function saveFields(array $field, array &$context) {
    $attributes = &$field['attributes'];
    $field['id'] = str_replace('ga:', '', $field['id']);
    $attributes['type'] = strtolower($attributes['type']);
    $attributes['dataType'] = strtolower($attributes['dataType']);
    $attributes['status'] = strtolower($attributes['status']);
    $attributes['description'] = isset($attributes['description']) ? $attributes['description'] : '';
    $attributes['calculation'] = isset($attributes['calculation']) ? $attributes['calculation'] : NULL;

    // Allow other modules to alter Google Analytics fields before saving
    // in database.
    \Drupal::moduleHandler()->alter('google_analytics_reports_field_import', $field);

    \Drupal::database()->insert('google_analytics_reports_fields')
      ->fields([
        'gaid' => $field['id'],
        'type' => $attributes['type'],
        'data_type' => $attributes['dataType'],
        'column_group' => $attributes['group'],
        'ui_name' => $attributes['uiName'],
        'description' => $attributes['description'],
        'calculation' => $attributes['calculation'],
      ])
      ->execute();
    $context['results'][] = $field['id'];
  }

  /**
   * Display messages after importing Google Analytics fields.
   *
   * @param bool $success
   *   Indicates whether the batch process was successful.
   * @param array $results
   *   Results information passed from the processing callback.
   */
  public static function importFieldsFinished($success, $results) {
    if ($success) {
      drupal_set_message(t('Imported @count Google Analytics fields.', ['@count' => count($results)]));
      // Hook_views_data() doesn't see the GA fields before cleaning cache.
      drupal_flush_all_caches();
    }
    else {
      drupal_set_message(t('An error has occurred during importing Google Analytics fields.'), 'error');
    }
  }

}
