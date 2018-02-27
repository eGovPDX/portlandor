<?php

namespace Drupal\Tests\quickedit\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests that a Moderated Node can be Quick-Edited.
 *
 * @group quickedit
 */
class QuickEditContentModerationTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation',
    'contextual',
    'node',
    'quickedit',
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a content type with moderation enabled.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    /* @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();

    // Create a privileged user.
    $user = $this->drupalCreateUser([
      'access contextual links',
      'access toolbar',
      'access in-place editing',
      'access content',
      'create article content',
      'edit any article content',
      'view any unpublished content',
      'view latest version',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
    ]);
    $this->setCurrentUser($user);
    $this->drupalLogin($user);
  }

  /**
   * Tests Quick-Editing a Moderated Node's draft.
   */
  public function testModeratedQuickEdit() {
    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Change me',
      'moderation_state' => 'published',
    ]);
    $this->drupalGet('node/' . $node->id());

    $entity_selector = '[data-quickedit-entity-id="node/' . $node->id() . '"]';
    $field_selector = '[data-quickedit-field-id="node/' . $node->id() . '/title/' . $node->language()->getId() . '/full"]';

    // Create a forward revision.
    $node->moderation_state->setValue('draft');
    $node->save();
    $this->drupalGet('node/' . $node->id() . '/latest');

    // Wait until Quick Edit loads.
    $condition = "jQuery('" . $entity_selector . " .quickedit').length > 0";
    $this->assertJsCondition($condition, 10000);

    // Initiate Quick Editing.
    $this->click('.contextual-toolbar-tab button');
    $this->click($entity_selector . ' [data-contextual-id] > button');
    $this->click($entity_selector . ' [data-contextual-id] .quickedit > a');
    $this->click($field_selector);
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Trigger an edit with Javascript (this is a "contenteditable" element).
    $this->getSession()->executeScript("jQuery('" . $field_selector . "').text('Hello world').trigger('keyup');");

    // To prevent 403s on save, we re-set our request (cookie) state. This is
    // done when explicitly making requests with drupalGet() and submitForm(),
    // but not when triggering AJAX requests within a functional test.
    $this->prepareRequest();

    // Save the change.
    $this->click('.quickedit-button.action-save');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that the default revision does not include this change.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextNotContains('Hello world');
    $this->assertSession()->pageTextContains('Change me');
  }

}
