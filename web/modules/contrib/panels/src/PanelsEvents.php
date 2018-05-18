<?php

namespace Drupal\panels;

/**
 * Contains events fired by Panels at various points.
 */
final class PanelsEvents {

  /**
   * The name of the event triggered before a Panels display variant is saved.
   *
   * This event allows modules to react before a Panels display variant is
   * saved. The event listener method receives a
   * \Drupal\panels\PanelsVariantEvent instance.
   *
   * @Event
   *
   * @var string
   */
  const VARIANT_PRE_SAVE = 'panels.variant.pre_save';

  /**
   * The name of the event triggered after a Panels display variant is saved.
   *
   * This event allows modules to react after a Panels display variant is saved.
   * The event listener method receives a \Drupal\panels\PanelsVariantEvent
   * instance. Note that changes to the variant made by subscribers to this
   * event will NOT be persisted.
   *
   * @Event
   *
   * @var string
   */
  const VARIANT_POST_SAVE = 'panels.variant.post_save';

}
