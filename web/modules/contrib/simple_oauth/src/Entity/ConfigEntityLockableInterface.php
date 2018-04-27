<?php

namespace Drupal\simple_oauth\Entity;

interface ConfigEntityLockableInterface {

  /**
   * Checks if the entity is locked against changes.
   *
   * @return bool
   */
  public function isLocked();

  /**
   * Locks the entity.
   */
  public function lock();

  /**
   * Unlocks the entity.
   */
  public function unlock();

}
