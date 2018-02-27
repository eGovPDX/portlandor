<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\jsonapi\Query\Sort;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * The normalizer used for JSON API sorts.
 *
 * @internal
 */
class SortNormalizer implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = Sort::class;

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return $type == $this->supportedInterfaceOrClass;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $expanded = $this->expand($data);
    return new Sort($expanded);
  }

  /**
   * {@inheritdoc}
   */
  protected function expand($sort) {
    if (empty($sort)) {
      throw new BadRequestHttpException('You need to provide a value for the sort parameter.');
    }

    // Expand a JSON API compliant sort into a more expressive sort parameter.
    if (is_string($sort)) {
      $sort = $this->expandFieldString($sort);
    }

    // Expand any defaults into the sort array.
    $expanded = [];
    foreach ($sort as $sort_index => $sort_item) {
      $expanded[$sort_index] = $this->expandItem($sort_index, $sort_item);
    }

    return $expanded;
  }

  /**
   * Expands a simple string sort into a more expressive sort that we can use.
   *
   * @param string $fields
   *   The comma separated list of fields to expand into an array.
   *
   * @return array
   *   The expanded sort.
   */
  protected function expandFieldString($fields) {
    return array_map(function ($field) {
      $sort = [];

      if ($field[0] == '-') {
        $sort[Sort::DIRECTION_KEY] = 'DESC';
        $sort[Sort::PATH_KEY] = substr($field, 1);
      }
      else {
        $sort[Sort::DIRECTION_KEY] = 'ASC';
        $sort[Sort::PATH_KEY] = $field;
      }

      return $sort;
    }, explode(',', $fields));
  }

  /**
   * Expands a sort item in case a shortcut was used.
   *
   * @param string $sort_index
   *   Unique identifier for the sort parameter being expanded.
   * @param array $sort_item
   *   The raw sort item.
   *
   * @return array
   *   The expanded sort item.
   */
  protected function expandItem($sort_index, array $sort_item) {
    $defaults = [
      Sort::DIRECTION_KEY => 'ASC',
      Sort::LANGUAGE_KEY => NULL,
    ];

    if (!isset($sort_item[Sort::PATH_KEY])) {
      throw new BadRequestHttpException('You need to provide a field name for the sort parameter.');
    }

    $expected_keys = [
      Sort::PATH_KEY,
      Sort::DIRECTION_KEY,
      Sort::LANGUAGE_KEY,
    ];

    $expanded = array_merge($defaults, $sort_item);

    // Verify correct sort keys.
    if (count(array_diff($expected_keys, array_keys($expanded))) > 0) {
      throw new BadRequestHttpException('You have provided an invalid set of sort keys.');
    }

    return $expanded;
  }

}
