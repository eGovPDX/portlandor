<?php

namespace Acquia\LightningExtension\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains miscellaneous step definitions for working with HTML elements.
 */
class ElementContext extends DrupalSubContextBase {

  /**
   * Asserts that an element is empty.
   *
   * @param string $selector
   *   The element's CSS selector.
   *
   * @throws ExpectationException
   *   If the element contains any HTML (leading and trailing white space is
   *   trimmed out).
   *
   * @Then the :selector element(s) should be empty
   */
  public function assertEmptyElement($selector) {
    $content = $this->assertSession()
      ->elementExists('css', $selector)
      ->getHtml();

    if (trim($content)) {
      throw new ExpectationException("Expected element '$selector' to be empty, but it is not.", $this->getSession()->getDriver());
    }
  }

  /**
   * Clears a field.
   *
   * @param string $field
   *   The field to clear.
   * @param ElementInterface $container
   *   (optional) The element that contains the field.
   *
   * @When I clear :field
   */
  public function clearField($field, ElementInterface $container = NULL) {
    $this->assertSession()->fieldExists($field, $container)->setValue(FALSE);
  }

  /**
   * Asserts that a form field is present.
   *
   * Oddly, MinkContext does not appear to provide a step definition for this.
   *
   * @param string $field
   *   The field locator.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The form field element.
   *
   * @Then I should see a(n) :field field
   */
  public function assertField($field) {
    return $this->assertSession()->fieldExists($field);
  }

  /**
   * Asserts that a field element exists and is disabled.
   *
   * @param string $field
   *   The field locator.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The disabled form field element.
   *
   * @Then I should see a disabled :field field
   * @Then the :field field should be disabled
   */
  public function assertDisabledField($field) {
    return $this->assertSession()
      ->elementAttributeExists('named', ['field', $field], 'disabled');
  }

  /**
   * Asserts that a form field is not present.
   *
   * Oddly, MinkContext does not appear to provide a step definition for this.
   *
   * @param string $field
   *   The field locator.
   *
   * @Then I should not see a(n) :field field
   */
  public function assertNoField($field) {
    $this->assertSession()->fieldNotExists($field);
  }

  /**
   * Asserts that a select list has a particular option.
   *
   * @param string $select
   *   The select list to check.
   * @param string $option
   *   The option to look for.
   *
   * @Then the :select field should have a(n) :option option
   */
  public function assertOption($select, $option) {
    $this->assertSession()
      ->elementExists('named', ['option', $option], $this->assertField($select));
  }

  /**
   * Asserts that a select list does not have a particular option.
   *
   * @param string $select
   *   The select list to check.
   * @param string $option
   *   The option to look for.
   *
   * @Then the :select field should not have a(n) :option option
   */
  public function assertNoOption($select, $option) {
    $this->assertSession()
      ->elementNotExists('named', ['option', $option], $this->assertField($select));
  }

  /**
   * Asserts that a select list has a set of options.
   *
   * @param string $select
   *   The select list to check.
   * @param \Behat\Gherkin\Node\PyStringNode $options
   *   The options to look for.
   *
   * @Then the :select field should have options:
   */
  public function assertOptions($select, PyStringNode $options) {
    foreach ($options->getStrings() as $option) {
      $this->assertOption($select, $option);
    }
  }

  /**
   * Asserts that a select list does not have a set of options.
   *
   * @param string $select
   *   The select list to check.
   * @param \Behat\Gherkin\Node\PyStringNode $options
   *   The options to look for.
   *
   * @Then the :select field should not have options:
   */
  public function assertNoOptions($select, PyStringNode $options) {
    foreach ($options->getStrings() as $option) {
      $this->assertNoOption($select, $option);
    }
  }

  /**
   * Switches to a frame.
   *
   * @param string $name
   *   The frame name.
   *
   * @When I switch to the :name frame
   * @When I enter the :name frame
   */
  public function enterFrame($name) {
    $this->getSession()->switchToIFrame($name);
  }

  /**
   * Switches out of an frame, into the main window.
   *
   * @When I (switch|return) to the window
   * @When I (exit|leave) the frame
   */
  public function exitFrame() {
    $this->getSession()->switchToWindow();
  }

  /**
   * Asserts that vertical tab set exists.
   *
   * @param ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The vertical tab set.
   *
   * @Then I should see a set of vertical tabs
   */
  public function assertVerticalTabs(ElementInterface $container = NULL) {
    return $this
      ->assertSession()
      ->elementExists('css', 'ul.vertical-tabs__menu', $container);
  }

  /**
   * Asserts that a specific vertical tab exists.
   *
   * @param string $tab
   *   The text of the tab.
   * @param ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The vertical tab.
   *
   * @Then I should see a :tab vertical tab
   * @Then I should see the :tab vertical tab
   */
  public function assertVerticalTab($tab, ElementInterface $container = NULL) {
    return $this
      ->assertSession()
      ->elementExists(
        'named',
        ['link', $tab],
        $this->assertVerticalTabs($container)
      );
  }

  /**
   * Asserts that a specific vertical tab does not exist.
   *
   * @param string $tab
   *   The text of the tab.
   * @param ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The vertical tab.
   *
   * @Then I should not see a :tab vertical tab
   * @Then I should not see the :tab vertical tab
   */
  public function assertNoVerticalTab($tab, ElementInterface $container = NULL) {
    $this
      ->assertSession()
      ->elementNotExists(
        'named',
        ['link', $tab],
        $this->assertVerticalTabs($container)
      );
  }

  /**
   * Asserts that a specific set of vertical tabs exists.
   *
   * @param \Behat\Gherkin\Node\PyStringNode $tabs
   *   The text of the tabs, one per line.
   * @param ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @Then I should see (the )vertical tabs:
   */
  public function assertVerticalTabMultiple(PyStringNode $tabs, ElementInterface $container = NULL) {
    foreach ($tabs->getStrings() as $tab) {
      $this->assertVerticalTab($tab, $container);
    }
  }

  /**
   * Asserts that a set of specific vertical tabs exists.
   *
   * @param \Behat\Gherkin\Node\PyStringNode $tabs
   *   The text of the tabs, one per line.
   * @param ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @Then I should not see (the )vertical tabs:
   */
  public function assertNotVerticalTabMultiple(PyStringNode $tabs, ElementInterface $container = NULL) {
    foreach ($tabs->getStrings() as $tab) {
      $this->assertNoVerticalTab($tab, $container);
    }
  }

  /**
   * Asserts the existence of a <details> element by its summary text.
   *
   * @param string $summary
   *   The exact summary text.
   * @param ElementInterface $container
   *   The element in which to search for the <details> element.
   *
   * @return \Behat\Mink\Element\NodeElement|bool
   *   The <details> element, or FALSE if it was not found.
   */
  protected function findDetails($summary, ElementInterface $container) {
    /** @var \Behat\Mink\Element\NodeElement $element */
    foreach ($container->findAll('css', 'details > summary') as $element) {
      if ($element->getText() == $summary) {
        return $element->getParent();
      }
    }
    return FALSE;
  }

  /**
   * Asserts the existence of a <details> element by its summary text.
   *
   * @param string $summary
   *   The exact summary text.
   * @param ElementInterface $container
   *   (optional) The element in which to search for the <details> element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The <details> element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException if the element is
   * not found in the container.
   *
   * @Then I should see a(n) :summary details element
   */
  public function assertDetails($summary, ElementInterface $container = NULL) {
    $session = $this->getSession();

    $element = $this->findDetails($summary, $container ?: $session->getPage());
    if ($element) {
      return $element;
    }
    else {
      throw new ElementNotFoundException($session->getDriver(), 'details', 'summary', $summary);
    }
  }

  /**
   * Asserts the existence of an open <details> element by its summary text.
   *
   * @param string $summary
   *   The exact summary text.
   * @param ElementInterface $container
   *   (optional) The element in which to search for the <details> element.
   *
   * @throws \Behat\Mink\Exception\ExpectationException if the <details> element
   * is closed.
   *
   * @Then I should see an open :summary details element
   */
  public function assertOpenDetails($summary, ElementInterface $container = NULL) {
    $open = $this->assertDetails($summary, $container)->hasAttribute('open');

    if (! $open) {
      throw new ExpectationException(
        "Expected '$summary' details to be open, but it is closed.",
        $this->getSession()->getDriver()
      );
    }
  }

  /**
   * Asserts the existence of a closed <details> element by its summary text.
   *
   * @param string $summary
   *   The exact summary text.
   * @param ElementInterface $container
   *   (optional) The element in which to search for the <details> element.
   *
   * @throws \Behat\Mink\Exception\ExpectationException if the <details> element
   * is open.
   *
   * @Then I should see a closed :summary details element
   */
  public function assertClosedDetails($summary, ElementInterface $container = NULL) {
    try {
      $this->assertOpenDetails($summary, $container);
    }
    catch (ExpectationException $e) {
      return;
    }

    throw new ExpectationException(
      "Expected '$summary' details to be closed, but it is open.",
      $this->getSession()->getDriver()
    );
  }

  /**
   * Clicks an arbitrary element, found by CSS selector.
   *
   * @param string $selector
   *   The CSS selector.
   *
   * @When I click the :selector element
   */
  public function click($selector) {
    $element = $this->assertSession()->elementExists('css', $selector);

    try {
      $this->scrollTo($selector);
    }
    catch (UnsupportedDriverActionException $e) {
      // Don't worry about it.
    }
    $element->click();
  }

  /**
   * Scrolls an element into the viewport.
   *
   * @param string $selector
   *   The element's CSS selector.
   *
   * @When I scroll to the :selector element
   */
  public function scrollTo($selector) {
    $this->getSession()
      ->executeScript('document.querySelector("' . addslashes($selector) . '").scrollIntoView()');
  }

  /**
   * Asserts that a minimum number of elements match a CSS selector.
   *
   * @param string $selector
   *   The selector.
   * @param int $n
   *   The number of elements expected to match the selector.
   *
   * @throws ExpectationException if the number of elements that match the
   * selector is less than expected.
   *
   * @Then at least :n element(s) should match :selector
   */
  public function matchAtLeast($selector, $n) {
    $session = $this->getSession();

    $count = count($session->getPage()->findAll('css', $selector));

    if ($count < $n) {
      throw new ExpectationException("Expected '$selector' to match at least $n elements, but it matched $count.", $session->getDriver());
    }
  }

  /**
   * Asserts that a block with a specific plugin ID is present.
   *
   * @param string $plugin_id
   *   The block plugin ID.
   *
   * @return ElementInterface
   *   The block element.
   *
   * @Then I should see a :plugin_id block
   */
  public function assertBlockExists($plugin_id) {
    return $this->assertSession()
      ->elementExists('css', "[data-block-plugin-id='$plugin_id']");
  }

  /**
   * Asserts that a block with a specific plugin ID is not present.
   *
   * @param string $plugin_id
   *   The block plugin ID.
   *
   * @Then I should not see a :plugin_id block
   */
  public function assertBlockNotExists($plugin_id) {
    $this->assertSession()
      ->elementNotExists('css', "[data-block-plugin-id='$plugin_id']");
  }

  /**
   * Asserts that an element has contextual links.
   *
   * @param \Behat\Mink\Element\ElementInterface $element
   *   The element which is expected to contain contextual links.
   *
   * @return ElementInterface
   *   The list of contextual links.
   */
  protected function assertContextualLinks(ElementInterface $element) {
    return $this->assertSession()
      ->elementExists('css', 'ul.contextual-links', $element);
  }

  /**
   * Asserts that an element has a specific contextual link.
   *
   * @param \Behat\Mink\Element\ElementInterface $element
   *   The element which is expectd to contain the contextual link.
   * @param (string) $link_class
   *   The CSS class of the contextual link.
   *
   * @return ElementInterface
   *   The contextual link.
   */
  protected function assertContextualLink(ElementInterface $element, $link_class) {
    return $this->assertSession()
      ->elementExists('css', "li.$link_class", $this->assertContextualLinks($element));
  }

  /**
   * Asserts that a block has contextual link(s).
   *
   * @param string $plugin_id
   *   The block plugin ID.
   * @param string $link_class
   *   (optional) The CSS class of a specific contextual link.
   *
   * @see ::assertContextualLinks()
   * @see ::assertContextualLink()
   * @see ::assertBlockExists()
   *
   * @Then the :plugin_id block should have contextual links
   * @Then the :plugin_id block should have a :link_class contextual link
   * @Then I should see a :plugin_id block with contextual links
   * @Then I should see a :plugin_id block with a :link_class contextual link
   */
  public function assertBlockHasContextual($plugin_id, $link_class = NULL) {
    $element = $this->assertBlockExists($plugin_id);

    return $link_class
      ? $this->assertContextualLink($element, $link_class)
      : $this->assertContextualLinks($element);
  }

}
