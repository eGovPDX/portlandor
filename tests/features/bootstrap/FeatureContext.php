<?php

// use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Defines general application features used by other feature files.
 *
 * @codingStandardsIgnoreStart
 */
// class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {
class FeatureContext extends Behat\MinkExtension\Context\MinkContext implements SnippetAcceptingContext {

}
