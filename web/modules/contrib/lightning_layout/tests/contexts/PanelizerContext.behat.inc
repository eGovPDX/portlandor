<?php

namespace Acquia\LightningExtension\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;

class PanelizerContext extends DrupalSubContextBase {

  use AwaitTrait;

  /**
   * Places a block into a Panelizer layout via the wizard.
   *
   * @param string $label
   *   The text name of the block.
   * @param string $region
   *   The name of the region in which to place the block.
   *
   * @When I place the :label block into the :region panelizer region
   */
  public function placeBlock($label, $region) {
    $assert = $this->assertSession();

    $assert->elementExists('named', ['link', 'Add new block'])->click();
    $this->awaitAjax();

    $assert->elementExists('named', ['link', $label])->click();
    $this->awaitAjax();

    $this->getSession()->getPage()->selectFieldOption('region', $region);
    $this->awaitAjax();

    $assert->elementExists('named', ['button', 'Add block'])->press();
    $this->awaitAjax();

    $this->assertBlock($label, $region);
  }

  /**
   * Removes a block from a Panelizer layout via the wizard.
   *
   * Assumes that exactly one block with the given name exists in the given
   * region.
   *
   * @param string $label
   *   The label of the block to remove.
   * @param string $region
   *   The machine name of the region in which the block is currently placed.
   *
   * @When I remove the :label block from the :region panelizer region
   */
  public function removeBlock($label, $region) {
    $row = $this->assertBlock($label, $region);

    $assert = $this->assertSession();
    $drop_button = $assert->elementExists('css', 'ul.dropbutton', $row);
    $assert->elementExists('css', 'li.dropbutton-toggle', $drop_button)->click();
    $assert->elementExists('named', ['link', 'Delete'], $drop_button)->click();
  }

  /**
   * Asserts that a block is present in a specific region of a Panelizer layout.
   *
   * @param string $label
   *   The block label.
   * @param string $region
   *   The machine name of the region in which the block is expected to be.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The block's row in the table.
   *
   * @throws \Behat\Mink\Exception\ExpectationException if the block is not
   * present as expected.
   *
   * @Then the :label block should be in the :region region
   */
  public function assertBlock($label, $region) {
    $row = $this->getBlockRow($label, $region);
    if ($row) {
      return $row;
    }
    else {
      throw new ExpectationException("Expected block '{$label}' to be present in '{$region}' region.", $this->getSession()->getDriver());
    }
  }

  /**
   * Returns the table row for a specific block in a specific region.
   *
   * @param string $block_label
   *   The label of the block to locate.
   * @param string $region
   *   The machine name of the region in which the block is expected to be.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The row element, or null if one was not found.
   */
  protected function getBlockRow($block_label, $region) {
    $page = $this->getSession()->getPage();

    // array_map() callback. Traverses from a region select list to the table
    // row that contains it.
    $row_map = function (NodeElement $select) {
      // $select->containing DIV->table cell->table row.
      return $select->getParent()->getParent()->getParent();
    };

    $elements = array_filter(
      $page->findAll('css', 'table#blocks tr > td > div > select.block-region-select'),
      function (NodeElement $element) use ($region) {
        return $element->getValue() == $region;
      }
    );

    /** @var NodeElement $row */
    foreach (array_map($row_map, $elements) as $row) {
      // The first cell is the one with the label; find() will return the first
      // matched element, which should be the first cell.
      $row_label = $row->find('css', 'td')->getText();
      if (trim($row_label) == $block_label) {
        return $row;
      }
    }
  }

}
