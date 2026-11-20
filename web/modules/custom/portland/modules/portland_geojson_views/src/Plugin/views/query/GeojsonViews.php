<?php

namespace Drupal\portland_geojson_views\Plugin\views\query;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Zendesk views query plugin which wraps calls to the Zendesk Tickets API in order to
 * expose the results to views.
 *
 * @ViewsQuery(
 *   id = "portland_geojson",
 *   title = @Translation("Portland GeoJSON Views"),
 *   help = @Translation("GeoJSON features data provided by an external API endpoint.")
 * )
 */
class GeojsonViews extends QueryPluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {

    $url = $view->query->options['feed_url'];
    $idProperty = !empty($view->query->options['id_property']) ? $view->query->options['id_property'] : 'OBJECTID';

    $lifetime = isset($view->query->options['cache_lifetime']) ? (int) $view->query->options['cache_lifetime'] : 0;
    $data = $this->fetchFeed($url, $lifetime);

    $index = 0;

    foreach($data['features'] as $feature) {
      // the asset ID defaults to the OBJECTID property; feeds that use a different
      // property (e.g. STATION for fire stations) set the "id_property" query option.
      $row['feature_id'] = $feature['properties'][$idProperty];

      // geometry
      // these properties are universal across all geoJSON feeds.
      $row['feature_type'] = $feature['geometry']['type'];
      $row['feature_geometry'] = json_encode($feature['geometry']);

      // properties
      // not all properties are applicable to all geoJSON feeds that this module might consume.
      // any new possible properties need to be hard coded here before use.
      $row['tag_number'] = array_key_exists('tag_number', $feature['properties']) ? $feature['properties']['tag_number'] : "";
      $row['collection_days'] = array_key_exists('collection_days', $feature['properties']) ? $feature['properties']['collection_days'] : "";
      $row['meter_distr'] = array_key_exists('MeterDistr', $feature['properties']) ? $feature['properties']['MeterDistr'] : "";
      $row['station_address'] = array_key_exists('ADDRESS', $feature['properties']) ? $feature['properties']['ADDRESS'] : "";

      $row['index'] = $index;
      $index = $index + 1;
      $view->result[] = new ResultRow($row);
    }

  // {
  //   "type": "Feature",
  //   "id": 9,
  //   "geometry": {
  //     "type": "Point",
  //     "coordinates": [
  //       -122.68270238642894,
  //       45.518593885736628
  //     ]
  //   },
  //   "properties": {
  //     "OBJECTID": 9
  //   }
  // },


  }

  /**
   * Fetches the feed, using a cached copy when available.
   *
   * On failure, the last cached copy is served even if expired. If there is
   * none, an empty feature collection is returned (and not cached) so the
   * location picker can degrade gracefully instead of the request failing.
   */
  protected function fetchFeed($url, $lifetime) {
    $cache = \Drupal::cache();
    $cid = 'portland_geojson_views:' . md5($url);

    if ($lifetime > 0 && ($cached = $cache->get($cid)) && !empty($cached->data['features'])) {
      return $cached->data;
    }

    $data = NULL;
    try {
      $response = \Drupal::httpClient()->request('GET', $url, ['timeout' => 10]);
      $decoded = json_decode((string) $response->getBody(), TRUE);
      if (is_array($decoded) && isset($decoded['features']) && is_array($decoded['features'])) {
        $data = $decoded;
      }
      else {
        \Drupal::logger('portland_geojson_views')->error('Invalid GeoJSON received from @url', ['@url' => $url]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('portland_geojson_views')->error('Unable to fetch GeoJSON from @url: @message', ['@url' => $url, '@message' => $e->getMessage()]);
    }

    if ($data !== NULL) {
      if ($lifetime > 0) {
        $cache->set($cid, $data, \Drupal::time()->getRequestTime() + $lifetime);
      }
      return $data;
    }

    // Serve stale data (expired items are still returned when allow_invalid is TRUE).
    if (($stale = $cache->get($cid, TRUE)) && !empty($stale->data['features'])) {
      return $stale->data;
    }

    return ['type' => 'FeatureCollection', 'features' => []];
  }

  public function ensureTable($table, $relationship = NULL) {
    return '';
  }

  public function addField($table, $field, $alias = '', $params = array()) {
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['feed_url'] = ['default' => ''];
    $options['id_property'] = ['default' => 'OBJECTID'];
    $options['cache_lifetime'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['feed_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('External geoJSON feed URL.'),
      '#default_value' => $this->options['feed_url'],
      '#description' => $this->t('This is the URL of the geoJSON feed to be consumed by the view.'),
    ];
    $form['id_property'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Feature ID property'),
      '#default_value' => $this->options['id_property'],
      '#description' => $this->t('The geoJSON feature property to use as the feature ID. Defaults to OBJECTID.'),
    ];
    $form['cache_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Feed cache lifetime (seconds)'),
      '#default_value' => $this->options['cache_lifetime'],
      '#min' => 0,
      '#description' => $this->t('How long to cache the external feed. 0 disables caching. An expired copy is still served if the feed is unavailable.'),
    ];
    parent::buildOptionsForm($form, $form_state);
  }



}
