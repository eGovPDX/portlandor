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
    'type' => 'numeric'
  ];
  $data['geojson_feature']['feature_type'] = [
    'title' => t('Feature type'),
    'help' => t('The type of geoJSON feature.'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard'
  ];
  $data['geojson_feature']['feature_geometry'] = [
    'title' => t('Feature geometry'),
    'help' => t('The geometry data associated with the geoJSON feature.'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard'
  ];
  $data['geojson_feature']['tag_number'] = [
    'title' => t('Trash can tag number'),
    'help' => t('The tag number associated with the trash can (property "tag_number" from geoJSON feed).'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard'
  ];
  $data['geojson_feature']['collection_days'] = [
    'title' => t('Trash can collection days'),
    'help' => t('The days of the week that the trash can is collected (property "collection_days" from geoJSON feed).'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard'
  ];
  $data['geojson_feature']['region_type'] = [
    'title' => t('Region type'),
    'help' => t('The type of region (property "TYPE" from geoJSON feed).'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard'
  ];

  return $data;
}

