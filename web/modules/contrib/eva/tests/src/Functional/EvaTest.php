<?php

namespace Drupal\Tests\eva\Functional;

/**
 * Preliminary tests for Eva.
 *
 * @group eva
 */
class EvaTest extends EvaTestBase {

    /**
     * Assert that the Eva of Articles appears on a Page.
     */
    function testEvaOnPage() {
        $assert = $this->assertSession();

        $this->drupalGet('/node/' . $this->nids['just_eva']);
        $assert->statusCodeEquals(200);

        $this->assertEquals(
            $this->article_count,
            \count($this->xpath('//div[contains(@class, "view-eva")]//div[contains(@class, "views-row")]')),
            sprintf('Found %d articles in Eva.', $this->article_count)
        );
    }

    /**
     * Test issue described in https://www.drupal.org/node/2873385.
     */
    function test2873385() {
        $assert = $this->assertSession();

        $this->drupalGet('/node/' . $this->nids['pages'][0]);
        $assert->statusCodeEquals(200);

        $this->drupalGet('/node/' . $this->nids['pages'][1]);
        $assert->statusCodeEquals(200);

        $this->drupalGet('/2873385');
        $assert->statusCodeEquals(200);

        // the view-eva's' should not all contain the same labels
        $evas = $this->xpath('//div[contains(@class, "view-eva")]');
        $all_links = [];
        foreach ($evas as $x) {
            $links = $x->findAll('xpath', '//a');
            $these_links = [];
            foreach ($links as $l) {
                $these_links[] = $l->getText();
            }
            $all_links[] = implode('-', $these_links);
        }
        $this->assertGreaterThan(
            1,
            \count(\array_unique($all_links)),
            'Found more than one unique Eva.'
        );
    }

}