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

    $json = file_get_contents($url);
    $data = array();
    $data = json_decode($json, true);

    $index = 0;

    foreach($data['features'] as $feature) {
      // the asset ID for the portlandmaps trash cans feed is stored in the OBJECTID property
      // TODO: find a way to make this abstract, so it doesn't have to be hard coded for feeds
      // that use a different property name.
      $row['feature_id'] = $feature['properties']['OBJECTID'];
      
      // geometry
      // these properties are universal across all geoJSON feeds.
      $row['feature_type'] = $feature['geometry']['type'];
      $row['feature_geometry'] = json_encode($feature['geometry']);

      // properties
      // not all properties are applicable to all geoJSON feeds that this module might consume.
      // any new possible properties need to be hard coded here before use.
      $row['tag_number'] = array_key_exists('tag_number', $feature['properties']) ? $feature['properties']['tag_number'] : "";
      $row['collection_days'] = array_key_exists('collection_days', $feature['properties']) ? $feature['properties']['collection_days'] : "";
      $row['region_type'] = array_key_exists('TYPE', $feature['properties']) ? $feature['properties']['TYPE'] : "";
      
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
    parent::buildOptionsForm($form, $form_state);
  }



}
