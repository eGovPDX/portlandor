<?php

namespace Drupal\Tests\simple_oauth\Unit\Authentication\Provider;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider;
use Drupal\simple_oauth\Server\ResourceServerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider
 * @group simple_oauth
 */
class SimpleOauthAuthenticationTest extends UnitTestCase {

  /**
   * The authentication provider.
   *
   * @var \Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProviderInterface
   */
  protected $provider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $resource_server = $this->prophesize(ResourceServerInterface::class);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->provider = new SimpleOauthAuthenticationProvider(
      $resource_server->reveal(),
      $entity_type_manager->reveal()
    );
  }

  /**
   * @covers ::hasTokenValue
   * @covers ::applies
   *
   * @dataProvider hasTokenValueProvider
   */
  public function testHasTokenValue(Request $request, $has_token) {
    $this->assertSame($has_token, $this->provider->hasTokenValue($request));
  }

  public function hasTokenValueProvider() {
    $data = [];

    // 1. Authentication header.
    $token = $this->getRandomGenerator()->name();
    $request = new Request();
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $data[] = [$request, TRUE];

    // 2. Authentication header. Trailing white spaces.
    $token = $this->getRandomGenerator()->name();
    $request = new Request();
    $request->headers->set('Authorization', '  Bearer ' . $token);
    $data[] = [$request, TRUE];

    // 3. Authentication header. No white spaces.
    $token = $this->getRandomGenerator()->name();
    $request = new Request();
    $request->headers->set('Authorization', 'Foo' . $token);
    $data[] = [$request, FALSE];

    // 4. Authentication header. Fail: no token.
    $request = new Request();
    $data[] = [$request, FALSE];

    return $data;
  }

}
