<?php

namespace Drupal\jsonapi_test_collection_count\ResourceType;

use Drupal\jsonapi\ResourceType\ResourceType;

class CountableResourceType extends ResourceType {

  /**
   * {@inheritdoc}
   */
  public function includeCount() {
    return TRUE;
  }

}
