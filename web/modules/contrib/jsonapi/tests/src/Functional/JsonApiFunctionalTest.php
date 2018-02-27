<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\jsonapi\Query\OffsetPage;

/**
 * @group jsonapi
 * @group legacy
 */
class JsonApiFunctionalTest extends JsonApiFunctionalTestBase {

  /**
   * Test the GET method.
   */
  public function testRead() {
    $this->createDefaultContent(61, 5, TRUE, TRUE, static::IS_NOT_MULTILINGUAL);
    // Unpublish the last entity, so we can check access.
    $this->nodes[60]->setUnpublished()->save();

    // 1. Load all articles (1st page).
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals(OffsetPage::SIZE_MAX, count($collection_output['data']));
    $this->assertSession()
      ->responseHeaderEquals('Content-Type', 'application/vnd.api+json');
    // 2. Load all articles (Offset 3).
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['page' => ['offset' => 3]],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals(OffsetPage::SIZE_MAX, count($collection_output['data']));
    $this->assertContains('page%5Boffset%5D=53', $collection_output['links']['next']);
    // 3. Load all articles (1st page, 2 items)
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['page' => ['limit' => 2]],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals(2, count($collection_output['data']));
    // 4. Load all articles (2nd page, 2 items).
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => [
        'page' => [
          'limit' => 2,
          'offset' => 2,
        ],
      ],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals(2, count($collection_output['data']));
    $this->assertContains('page%5Boffset%5D=4', $collection_output['links']['next']);
    // 5. Single article.
    $uuid = $this->nodes[0]->uuid();
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertArrayHasKey('type', $single_output['data']);
    $this->assertEquals($this->nodes[0]->getTitle(), $single_output['data']['attributes']['title']);

    // 5.1 Single article with access denied.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $this->nodes[60]->uuid()));
    $this->assertSession()->statusCodeEquals(403);

    $this->assertEquals('/data', $single_output['errors'][0]['source']['pointer']);
    $this->assertEquals('/node--article/' . $this->nodes[60]->uuid(), $single_output['errors'][0]['id']);

    // 6. Single relationship item.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/relationships/type'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertArrayHasKey('type', $single_output['data']);
    $this->assertArrayNotHasKey('attributes', $single_output['data']);
    $this->assertArrayHasKey('related', $single_output['links']);
    // 7. Single relationship image.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/relationships/field_image'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertArrayHasKey('type', $single_output['data']);
    $this->assertArrayNotHasKey('attributes', $single_output['data']);
    $this->assertArrayHasKey('related', $single_output['links']);
    // 8. Multiple relationship item.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/relationships/field_tags'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertArrayHasKey('type', $single_output['data'][0]);
    $this->assertArrayNotHasKey('attributes', $single_output['data'][0]);
    $this->assertArrayHasKey('related', $single_output['links']);
    // 9. Related tags with includes.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/field_tags', [
      'query' => ['include' => 'vid'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('taxonomy_term--tags', $single_output['data'][0]['type']);
    $this->assertArrayHasKey('tid', $single_output['data'][0]['attributes']);
    $this->assertContains(
      '/taxonomy_term/tags/',
      $single_output['data'][0]['links']['self']
    );
    $this->assertEquals(
      'taxonomy_vocabulary--taxonomy_vocabulary',
      $single_output['included'][0]['type']
    );
    // 10. Single article with includes.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid, [
      'query' => ['include' => 'uid,field_tags'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('node--article', $single_output['data']['type']);
    $first_include = reset($single_output['included']);
    $this->assertEquals(
      'user--user',
      $first_include['type']
    );
    $last_include = end($single_output['included']);
    $this->assertEquals(
      'taxonomy_term--tags',
      $last_include['type']
    );

    // 10b. Single article with nested includes.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid, [
      'query' => ['include' => 'field_tags,field_tags.vid'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('node--article', $single_output['data']['type']);
    $first_include = reset($single_output['included']);
    $this->assertEquals(
      'taxonomy_term--tags',
      $first_include['type']
    );
    $last_include = end($single_output['included']);
    $this->assertEquals(
      'taxonomy_vocabulary--taxonomy_vocabulary',
      $last_include['type']
    );

    // 11. Includes with relationships.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/relationships/uid', [
      'query' => ['include' => 'uid'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('user--user', $single_output['data']['type']);
    $this->assertArrayHasKey('related', $single_output['links']);
    $first_include = reset($single_output['included']);
    $this->assertEquals(
      'user--user',
      $first_include['type']
    );
    $this->assertFalse(empty($first_include['attributes']));
    $this->assertTrue(empty($first_include['attributes']['mail']));
    $this->assertTrue(empty($first_include['attributes']['pass']));
    // 12. Collection with one access denied.
    $this->nodes[1]->set('status', FALSE);
    $this->nodes[1]->save();
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['page' => ['limit' => 2]],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals(1, count($single_output['data']));
    $this->assertEquals(1, count($single_output['meta']['errors']));
    $this->assertEquals(403, $single_output['meta']['errors'][0]['status']);
    $this->assertEquals('/node--article/' . $this->nodes[1]->uuid(), $single_output['meta']['errors'][0]['id']);
    $this->assertFalse(empty($single_output['meta']['errors'][0]['source']['pointer']));
    $this->nodes[1]->set('status', TRUE);
    $this->nodes[1]->save();
    // 13. Test filtering when using short syntax.
    $filter = [
      'uid.uuid' => ['value' => $this->user->uuid()],
      'field_tags.uuid' => ['value' => $this->tags[0]->uuid()],
    ];
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter, 'include' => 'uid,field_tags'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThan(0, count($single_output['data']));
    // 14. Test filtering when using long syntax.
    $filter = [
      'and_group' => ['group' => ['conjunction' => 'AND']],
      'filter_user' => [
        'condition' => [
          'path' => 'uid.uuid',
          'value' => $this->user->uuid(),
          'memberOf' => 'and_group',
        ],
      ],
      'filter_tags' => [
        'condition' => [
          'path' => 'field_tags.uuid',
          'value' => $this->tags[0]->uuid(),
          'memberOf' => 'and_group',
        ],
      ],
    ];
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter, 'include' => 'uid,field_tags'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThan(0, count($single_output['data']));
    // 15. Test filtering when using invalid syntax.
    $filter = [
      'and_group' => ['group' => ['conjunction' => 'AND']],
      'filter_user' => [
        'condition' => [
          'name-with-a-typo' => 'uid.uuid',
          'value' => $this->user->uuid(),
          'memberOf' => 'and_group',
        ],
      ],
    ];
    $this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]);
    $this->assertSession()->statusCodeEquals(400);
    // 16. Test filtering on the same field.
    $filter = [
      'or_group' => ['group' => ['conjunction' => 'OR']],
      'filter_tags_1' => [
        'condition' => [
          'path' => 'field_tags.uuid',
          'value' => $this->tags[0]->uuid(),
          'memberOf' => 'or_group',
        ],
      ],
      'filter_tags_2' => [
        'condition' => [
          'path' => 'field_tags.uuid',
          'value' => $this->tags[1]->uuid(),
          'memberOf' => 'or_group',
        ],
      ],
    ];
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter, 'include' => 'field_tags'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(2, count($single_output['included']));
    // 17. Single user (check fields lacking 'view' access).
    $user_url = Url::fromRoute('jsonapi.user--user.individual', [
      'user' => $this->user->uuid(),
    ]);
    $response = $this->request('GET', $user_url, [
      'auth' => [
        $this->userCanViewProfiles->getUsername(),
        $this->userCanViewProfiles->pass_raw,
      ],
    ]);
    $single_output = Json::decode($response->getBody()->__toString());
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('user--user', $single_output['data']['type']);
    $this->assertEquals($this->user->get('name')->value, $single_output['data']['attributes']['name']);
    $this->assertTrue(empty($single_output['data']['attributes']['mail']));
    $this->assertTrue(empty($single_output['data']['attributes']['pass']));
    // 18. Test filtering on the column of a link.
    $filter = [
      'linkUri' => [
        'condition' => [
          'path' => 'field_link.uri',
          'value' => 'https://',
          'operator' => 'STARTS_WITH',
        ],
      ],
    ];
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(1, count($single_output['data']));
    // 19. Test non-existing route without 'Accept' header.
    $this->drupalGet('/jsonapi/node/article/broccoli');
    $this->assertSession()->statusCodeEquals(404);
    // Without the 'Accept' header we cannot know we want the 404 error
    // formatted as JSON API.
    $this->assertSession()->responseHeaderContains('Content-Type', 'text/html');
    // 20. Test non-existing route with 'Accept' header.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/broccoli', [], [
      'Accept' => 'application/vnd.api+json',
    ]));
    $this->assertEquals(404, $single_output['errors'][0]['status']);
    $this->assertSession()->statusCodeEquals(404);
    // With the 'Accept' header we can know we want the 404 error formatted as
    // JSON API.
    $this->assertSession()->responseHeaderContains('Content-Type', 'application/vnd.api+json');
    // 21. Test the value of the computed 'url' field.
    $collection_output = Json::decode($this->drupalGet('/jsonapi/file/file'));
    $uri = $collection_output['data'][0]['attributes']['uri'];
    $this->assertEquals($collection_output['data'][0]['attributes']['url'], $uri);

    // Test documentation filtering examples.
    // 1. Only get published nodes.
    $filter = [
      'status-filter' => [
        'condition' => [
          'path' => 'status',
          'value' => 1,
        ],
      ],
    ];
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(OffsetPage::SIZE_MAX, count($collection_output['data']));
    // 2. Nested Filters: Get nodes created by user admin.
    $filter = [
      'name-filter' => [
        'condition' => [
          'path' => 'uid.name',
          'value' => $this->user->getAccountName(),
        ],
      ],
    ];
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(OffsetPage::SIZE_MAX, count($collection_output['data']));
    // 3. Filtering with arrays: Get nodes created by users [admin, john].
    $filter = [
      'name-filter' => [
        'condition' => [
          'path' => 'uid.name',
          'operator' => 'IN',
          'value' => [
            $this->user->getAccountName(),
            $this->getRandomGenerator()->name(),
          ],
        ],
      ],
    ];
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(OffsetPage::SIZE_MAX, count($collection_output['data']));
    // 4. Grouping filters: Get nodes that are published and create by admin.
    $filter = [
      'and-group' => [
        'group' => [
          'conjunction' => 'AND',
        ],
      ],
      'name-filter' => [
        'condition' => [
          'path' => 'uid.name',
          'value' => $this->user->getAccountName(),
          'memberOf' => 'and-group',
        ],
      ],
      'status-filter' => [
        'condition' => [
          'path' => 'status',
          'value' => 1,
          'memberOf' => 'and-group',
        ],
      ],
    ];
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(OffsetPage::SIZE_MAX, count($collection_output['data']));
    // 5. Grouping grouped filters: Get nodes that are promoted or sticky and
    //    created by admin.
    $filter = [
      'and-group' => [
        'group' => [
          'conjunction' => 'AND',
        ],
      ],
      'or-group' => [
        'group' => [
          'conjunction' => 'OR',
          'memberOf' => 'and-group',
        ],
      ],
      'admin-filter' => [
        'condition' => [
          'path' => 'uid.name',
          'value' => $this->user->getAccountName(),
          'memberOf' => 'and-group',
        ],
      ],
      'sticky-filter' => [
        'condition' => [
          'path' => 'sticky',
          'value' => 1,
          'memberOf' => 'or-group',
        ],
      ],
      'promote-filter' => [
        'condition' => [
          'path' => 'promote',
          'value' => 0,
          'memberOf' => 'or-group',
        ],
      ],
    ];
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals(0, count($collection_output['data']));
  }

  /**
   * Test POST, PATCH and DELETE.
   */
  public function testWrite() {
    $this->createDefaultContent(0, 3, FALSE, FALSE, static::IS_NOT_MULTILINGUAL);
    // 1. Successful post.
    $collection_url = Url::fromRoute('jsonapi.node--article.collection');
    $body = [
      'data' => [
        'type' => 'node--article',
        'attributes' => [
          'langcode' => 'en',
          'title' => 'My custom title',
          'status' => '1',
          'promote' => '1',
          'sticky' => '0',
          'default_langcode' => '1',
          'body' => [
            'value' => 'Custom value',
            'format' => 'plain_text',
            'summary' => 'Custom summary',
          ],
        ],
        'relationships' => [
          'type' => [
            'data' => [
              'type' => 'node_type--node_type',
              'id' => 'article',
            ],
          ],
          'uid' => [
            'data' => [
              'type' => 'user--user',
              'id' => '1',
            ],
          ],
          'field_tags' => [
            'data' => [
              [
                'type' => 'taxonomy_term--tags',
                'id' => $this->tags[0]->uuid(),
              ],
              [
                'type' => 'taxonomy_term--tags',
                'id' => $this->tags[1]->uuid(),
              ],
            ],
          ],
        ],
      ],
    ];
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(201, $response->getStatusCode());
    $this->assertArrayHasKey('uuid', $created_response['data']['attributes']);
    $uuid = $created_response['data']['attributes']['uuid'];
    $this->assertEquals(2, count($created_response['data']['relationships']['field_tags']['data']));
    $this->assertEquals($created_response['data']['links']['self'], $response->getHeader('Location')[0]);

    // 2. Authorization error.
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($body),
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertEquals('Forbidden', $created_response['errors'][0]['title']);

    // 2.1 Authorization error with a user without create permissions.
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($body),
      'auth' => [$this->userCanViewProfiles->getUsername(), $this->userCanViewProfiles->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertEquals('Forbidden', $created_response['errors'][0]['title']);

    // 3. Missing Content-Type error.
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => ['Accept' => 'application/vnd.api+json'],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(422, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertEquals('Unprocessable Entity', $created_response['errors'][0]['title']);
    // 4. Article with a duplicate ID.
    $invalid_body = $body;
    $invalid_body['data']['attributes']['nid'] = 1;
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($invalid_body),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(500, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertEquals('Internal Server Error', $created_response['errors'][0]['title']);
    // 5. Article with wrong reference UUIDs for tags.
    $body_invalid_tags = $body;
    $body_invalid_tags['data']['relationships']['field_tags']['data'][0]['id'] = 'lorem';
    $body_invalid_tags['data']['relationships']['field_tags']['data'][1]['id'] = 'ipsum';
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($body_invalid_tags),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(201, $response->getStatusCode());
    $this->assertEquals(0, count($created_response['data']['relationships']['field_tags']['data']));
    // 6. Serialization error.
    $response = $this->request('POST', $collection_url, [
      'body' => '{"bad json",,,}',
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(422, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertEquals('Unprocessable Entity', $created_response['errors'][0]['title']);
    // 7. Successful PATCH.
    $body = [
      'data' => [
        'id' => $uuid,
        'type' => 'node--article',
        'attributes' => ['title' => 'My updated title'],
      ],
    ];
    $individual_url = Url::fromRoute('jsonapi.node--article.individual', [
      'node' => $uuid,
    ]);
    $response = $this->request('PATCH', $individual_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('My updated title', $updated_response['data']['attributes']['title']);

    // 7.1 Unsuccessful PATCH due to access restrictions.
    $body = [
      'data' => [
        'id' => $uuid,
        'type' => 'node--article',
        'attributes' => ['title' => 'My updated title'],
      ],
    ];
    $individual_url = Url::fromRoute('jsonapi.node--article.individual', [
      'node' => $uuid,
    ]);
    $response = $this->request('PATCH', $individual_url, [
      'body' => Json::encode($body),
      'auth' => [$this->userCanViewProfiles->getUsername(), $this->userCanViewProfiles->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(403, $response->getStatusCode());

    // 8. Field access forbidden check.
    $body = [
      'data' => [
        'id' => $uuid,
        'type' => 'node--article',
        'attributes' => [
          'title' => 'My updated title',
          'status' => 0,
        ],
      ],
    ];
    $response = $this->request('PATCH', $individual_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals("The current user is not allowed to PATCH the selected field (status). The 'administer nodes' permission is required.",
      $updated_response['errors'][0]['detail']);

    $node = \Drupal::entityManager()->loadEntityByUuid('node', $uuid);
    $this->assertEquals(1, $node->get('status')->value, 'Node status was not changed.');
    // 9. Successful POST to related endpoint.
    $body = [
      'data' => [
        [
          'id' => $this->tags[2]->uuid(),
          'type' => 'taxonomy_term--tags',
        ],
      ],
    ];
    $relationship_url = Url::fromRoute('jsonapi.node--article.relationship', [
      'node' => $uuid,
      'related' => 'field_tags',
    ]);
    $response = $this->request('POST', $relationship_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(201, $response->getStatusCode());
    $this->assertEquals(3, count($updated_response['data']));
    $this->assertEquals('taxonomy_term--tags', $updated_response['data'][2]['type']);
    $this->assertEquals($this->tags[2]->uuid(), $updated_response['data'][2]['id']);
    // 10. Successful PATCH to related endpoint.
    $body = [
      'data' => [
        [
          'id' => $this->tags[1]->uuid(),
          'type' => 'taxonomy_term--tags',
        ],
      ],
    ];
    $response = $this->request('PATCH', $relationship_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertCount(1, $updated_response['data']);
    $this->assertEquals('taxonomy_term--tags', $updated_response['data'][0]['type']);
    $this->assertEquals($this->tags[1]->uuid(), $updated_response['data'][0]['id']);
    // 11. Successful DELETE to related endpoint.
    $payload = $updated_response;
    $response = $this->request('DELETE', $relationship_url, [
      // Send a request with no body.
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(
      'You need to provide a body for DELETE operations on a relationship (field_tags).',
      $updated_response['errors'][0]['detail']
    );
    $this->assertEquals(400, $response->getStatusCode());
    $response = $this->request('DELETE', $relationship_url, [
      // Send a request with no authentication.
      'body' => Json::encode($payload),
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(403, $response->getStatusCode());
    $response = $this->request('DELETE', $relationship_url, [
      // Remove the existing relationship item.
      'body' => Json::encode($payload),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(201, $response->getStatusCode());
    $this->assertCount(0, $updated_response['data']);
    // 12. PATCH with invalid title and body format.
    $body = [
      'data' => [
        'id' => $uuid,
        'type' => 'node--article',
        'attributes' => [
          'title' => '',
          'body' => [
            'value' => 'Custom value',
            'format' => 'invalid_format',
            'summary' => 'Custom summary',
          ],
        ],
      ],
    ];
    $response = $this->request('PATCH', $individual_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(422, $response->getStatusCode());
    $this->assertCount(2, $updated_response['errors']);
    for ($i = 0; $i < 2; $i++) {
      $this->assertEquals("Unprocessable Entity", $updated_response['errors'][$i]['title']);
      $this->assertEquals(422, $updated_response['errors'][$i]['status']);
      $this->assertEquals(0, $updated_response['errors'][$i]['code']);
    }
    $this->assertEquals("title: This value should not be null.", $updated_response['errors'][0]['detail']);
    $this->assertEquals("body.0.format: The value you selected is not a valid choice.", $updated_response['errors'][1]['detail']);
    $this->assertEquals("/data/attributes/title", $updated_response['errors'][0]['source']['pointer']);
    $this->assertEquals("/data/attributes/body/format", $updated_response['errors'][1]['source']['pointer']);
    // 13. PATCH with field that doesn't exist on Entity.
    $body = [
      'data' => [
        'id' => $uuid,
        'type' => 'node--article',
        'attributes' => [
          'field_that_doesnt_exist' => 'foobar',
        ],
      ],
    ];
    $response = $this->request('PATCH', $individual_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals("The provided field (field_that_doesnt_exist) does not exist in the entity with ID $uuid.",
      $updated_response['errors']['0']['detail']);
    // 14. Successful DELETE.
    $response = $this->request('DELETE', $individual_url, [
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    $response = $this->request('GET', $individual_url, []);
    $this->assertEquals(404, $response->getStatusCode());
  }

}
