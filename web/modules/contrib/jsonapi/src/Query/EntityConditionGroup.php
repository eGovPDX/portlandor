<?php

namespace Drupal\jsonapi\Query;

/**
 * A condition group for the EntityQuery.
 *
 * @internal
 */
class EntityConditionGroup {

  /**
   * The AND conjunction value.
   *
   * @var array
   */
  protected static $allowedConjunctions = ['AND', 'OR'];

  /**
   * The conjunction.
   *
   * @var string
   */
  protected $conjunction;

  /**
   * The members of the condition group.
   *
   * @var \Drupal\jsonapi\Query\EntityCondition[]
   */
  protected $members;

  /**
   * Constructs a new condition group object.
   *
   * @param string $conjunction
   *   The group conjunction to use.
   * @param array $members
   *   (optional) The group conjunction to use.
   */
  public function __construct($conjunction, array $members = []) {
    if (!in_array($conjunction, self::$allowedConjunctions)) {
      throw new \InvalidArgumentException('Allowed conjunctions: AND, OR.');
    }
    $this->conjunction = $conjunction;
    $this->members = $members;
  }

  /**
   * The condition group conjunction.
   *
   * @return string
   *   The condition group conjunction.
   */
  public function conjunction() {
    return $this->conjunction;
  }

  /**
   * The members which belong to the the condition group.
   *
   * @return \Drupal\jsonapi\Query\EntityCondition[]
   *   The member conditions of this condition group.
   */
  public function members() {
    return $this->members;
  }

}
