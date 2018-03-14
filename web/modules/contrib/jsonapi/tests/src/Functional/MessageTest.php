<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;

/**
 * JSON API integration test for the "Message" content entity type.
 *
 * @group jsonapi
 */
class MessageTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['contact'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'contact_message';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'contact_message--camelids';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\contact\MessageInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $labelFieldName = 'subject';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['access site-wide contact form']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    if (!ContactForm::load('camelids')) {
      // Create a "Camelids" contact form.
      ContactForm::create([
        'id' => 'camelids',
        'label' => 'Llama',
        'message' => 'Let us know what you think about llamas',
        'reply' => 'Llamas are indeed awesome!',
        'recipients' => [
          'llama@example.com',
          'contact@example.com',
        ],
      ])->save();
    }

    $message = Message::create([
      'contact_form' => 'camelids',
      'subject' => 'Llama Gabilondo',
      'message' => 'Llamas are awesome!',
    ]);
    $message->save();

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    throw new \Exception('Not yet supported.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => 'contact_message--camelids',
        'attributes' => [
          'subject' => 'Dramallama',
          'message' => 'http://www.urbandictionary.com/define.php?term=drama%20llama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($method === 'POST') {
      return "The 'access site-wide contact form' permission is required.";
    }
    return parent::getExpectedUnauthorizedAccessMessage($method);
  }

  /**
   * {@inheritdoc}
   */
  public function testGetIndividual() {
    $this->markTestSkipped('Change this override in https://www.drupal.org/project/jsonapi/issues/2944977');
  }

  /**
   * {@inheritdoc}
   */
  public function testPatchIndividual() {
    $this->markTestSkipped('Change this override in https://www.drupal.org/project/jsonapi/issues/2944977');
  }

  /**
   * {@inheritdoc}
   */
  public function testDeleteIndividual() {
    $this->markTestSkipped('Change this override in https://www.drupal.org/project/jsonapi/issues/2944977');
  }

}
