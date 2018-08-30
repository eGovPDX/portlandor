<?php

namespace Drupal\portland_legacy_redirects\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Make sure end date/time is after start date/time.
 *
 * @Constraint(
 *   id = "relative_path",
 *   label = @Translation("Verifies path is relative.", context = "Validation"),
 *   type = "string"
 * )
 */
class RelativePathConstraint extends Constraint
{
  /**
   * Message shown when the path is not relative (i.e. /some/path/here).
   *
   * @var string
   */
  public $not_relative = "The legacy path must be relative (i.e. no protocol or domain) and start with a slash.";
  public $illegal_chars = "The legacy path contains invalid characters. It should only contain letters, numbers, and the following symbols: slash (/), dot (.), hyphen (-), and underscore (_)";
  public $duplicate_in_form = "A legacy path may only be entered once. Please remove the duplicate path.";
  public $duplicate_redirect = "This legacy path already redirects to a content node in the system. A path cannot redirect to multiple nodes.";
}