<?php

namespace Drupal\portland\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to modify accordion headings.
 *
 * @Filter(
 *   id = "filter_accordion_headings",
 *   title = @Translation("Modify accordion headings to maintain heading hierarchy"),
 *   description = @Translation("Modify accordion headings for accessibility. Must run after the CKEditor Accordion filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterAccordionHeadings extends FilterBase
{
  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode)
  {
    $result = new FilterProcessResult($text);

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    // Find all headings h1 to h6 and store them in a flat array.
    $headings = [];
    foreach ($xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6') as $heading) {
      if( $this->isWithinAccordionPanel($heading) ) {
        continue; // Skip headings that are within an accordion panel.
      }
      $headings[] = $heading;
    }
    if(empty($headings)) {
      return $result; // No headings found, nothing to process.
    }

    // Traverse the $headings array and process as described.
    foreach ($headings as $idx => $heading) {
      if (
        strtolower($heading->nodeName) === 'h3' &&
        $heading->hasAttribute('data-aria-accordion-heading') &&
        $heading->getAttribute('data-aria-accordion-heading') === 'data-aria-accordion-heading'
      ) {
        // Look for a previous heading without the attribute in the array.
        for ($i = $idx - 1; $i >= 0; $i--) {
          $prev = $headings[$i];
          $prev_name = strtolower($prev->nodeName);
          // Only consider H3-H6. The default H3 works as expected for H2
          if (in_array($prev_name, ['h3', 'h4', 'h5', 'h6'])) {
            if (
              !$prev->hasAttribute('data-aria-accordion-heading') ||
              $prev->getAttribute('data-aria-accordion-heading') !== 'data-aria-accordion-heading'
            ) {
              // Calculate new heading level: h4 if h3, h5 if h4, h6 if h5, h6 if h6
              $level = (int)substr($prev_name, 1) + 1;
              if ($level > 6) {
                $level = 6;
              }
              $new_heading = $dom->createElement('h' . $level);
              foreach ($heading->attributes as $attr) {
                $new_heading->setAttribute($attr->nodeName, $attr->nodeValue);
              }
              while ($heading->firstChild) {
                $new_heading->appendChild($heading->firstChild);
              }
              $heading->parentNode->replaceChild($new_heading, $heading);
              break;
            }
          }
        }
      }
    }

    $result->setProcessedText(Html::serialize($dom));
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE)
  {
    return $this->t('<p>Modify accordion headings for accessibility.</p>');
  }

  /**
   * Helper function to check if an element is within an accordion panel.
   */
  private function isWithinAccordionPanel(\DOMElement $element) {
    if(!$element || $element->nodeType !== XML_ELEMENT_NODE) {
      return false;
    }
    // Traverse up the DOM tree to check if the element is within <div data-aria-accordion-panel="data-aria-accordion-panel">
    $parent = $element->parentNode;
    while ($parent && $parent->nodeType === XML_ELEMENT_NODE) {
      if (
        strtolower($parent->nodeName) === 'div' &&
        $parent->hasAttribute('data-aria-accordion-panel') &&
        $parent->getAttribute('data-aria-accordion-panel') === 'data-aria-accordion-panel'
      ) {
        return true;
      }
      $parent = $parent->parentNode;
    }
    return false;
  }
}
