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
      $row['feature_id'] = $feature['id'];
      $row['feature_type'] = $feature['geometry']['type'];
      $row['feature_coordinates_lat'] = $feature['geometry']['coordinates'][0];
      $row['feature_coordinates_lon'] = $feature['geometry']['coordinates'][1];

      $index = $index + 1;
      $row['index'] = $index;
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
