<?php

/**
 * Implements hook_views_data().
 */
function portland_geojson_views_views_data() {
  $data = [];
  // Base data.
  $data['geojson_feature']['table']['group'] = t('GeoJSON feature');
  $data['geojson_feature']['table']['base'] = [
    'title' => t('GeoJSON feature'),
    'help' => t('GeoJSON features data provided by an external API endpoint.'),
    'query_id' => 'portland_geojson',
  ];

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

  // Fields.
  $data['geojson_feature']['feature_id'] = [
    'title' => t('Feature ID'),
    'help' => t('The ID of the geoJSON feature.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['geojson_feature']['feature_type'] = [
    'title' => t('Feature type'),
    'help' => t('The type of geoJSON feature.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['geojson_feature']['feature_coordinates'] = [
    'title' => t('Feature coordinates'),
    'help' => t('The coordinates associated with the geoJSON feature.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['geojson_feature']['feature_coordinates_lat'] = [
    'title' => t('Feature coordinates latitude'),
    'help' => t('The latitude coordinate associated with the geoJSON point feature.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['geojson_feature']['feature_coordinates_lon'] = [
    'title' => t('Feature coordinates longitude'),
    'help' => t('The longitude coordinate associated with the geoJSON point feature.'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}

