<?php

namespace Drupal\Tests\eva\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Browser testing for Eva.
 *
 */
abstract class EvaTestBase extends BrowserTestBase {

	/**
	 * Modules to install.
	 *
	 * @var array
	 */
	public static $modules = [
		'eva',
		'eva_test',
		'node',
		'views',
		'user',
		'text',
	];

	/**
	 * Number of articles to generate.
	 */
	protected $article_count = 20;

	/**
	 * Number of pages to generate.
	 */
	protected $page_count = 10;

	/**
	 * Hold the page NID.
	 */
	protected $nids = [];

	/**
	* {@inheritdoc}
	*/
	protected function setUp() {
		parent::setUp();

		$this->makeNodes();
	}

	/**
	* Create some example nodes.
	*/
	protected function makeNodes() {

		// single page for simple Eva test
		$node = $this->createNode([
			'title' => 'Test Eva',
			'type' => 'just_eva',
		]);
		$this->nids['just_eva'] = $node->id();

		// pages for lists-in-lists
		$this->nids['pages'] = [];
		for ($i = 0; $i < $this->page_count; $i++) {
			$node = $this->createNode([
				'title' => sprintf('Page %d', $i + 1),
				'type' => 'page_with_related_articles',
			]);
			$this->nids['pages'][] = $node->id();
		}

		// articles
		for ($i = 0; $i < $this->article_count; $i++) {
			$node = $this->createNode([
				'title' => sprintf('Article %d', $i + 1),
				'type' => 'mini',
			]);

			// associate articles with assorted pages
			$k = array_rand($this->nids['pages'], 1);
			$node->field_page[] = $this->nids['pages'][$k];
			$node->save();
		}
	}


}
