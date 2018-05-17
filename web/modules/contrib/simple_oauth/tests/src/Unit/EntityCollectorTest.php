<?php

namespace Drupal\Tests\simple_oauth\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\consumers\Entity\Consumer;
use Drupal\simple_oauth\Entity\Oauth2Token;
use Drupal\simple_oauth\ExpiredCollector;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\simple_oauth\ExpiredCollector
 * @group simple_oauth
 */
class EntityCollectorTest extends UnitTestCase {

  /**
   * @covers ::collect
   */
  public function testCollect() {
    list($expired_collector, $query,) = $this->buildProphecies();
    $query->condition('expire', 42, '<')->shouldBeCalledTimes(1);
    $this->assertArrayEquals([1, 52], array_map(function ($entity) {
      return $entity->id();
    }, $expired_collector->collect()));
  }

  /**
   * @covers ::collectForClient
   */
  public function testCollectForClient() {
    list($expired_collector, $query,) = $this->buildProphecies();
    $client = $this->prophesize(Consumer::class);
    $client->id()->willReturn(35);
    $query->condition('client', 35)->shouldBeCalledTimes(1);
    $tokens = $expired_collector->collectForClient($client->reveal());
    $this->assertArrayEquals([1, 52], array_map(function ($entity) {
      return $entity->id();
    }, $tokens));
  }

  /**
   * @covers ::collectForAccount
   */
  public function testCollectForAccount() {
    list($expired_collector, $token_query,,, $client_storage) = $this->buildProphecies();
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn(22);
    $token_query->condition('auth_user_id', 22)->shouldBeCalledTimes(1);
    $client_storage->loadByProperties([
      'user_id' => 22,
    ])->shouldBeCalledTimes(1);
    $token_query->condition('client', 6)->shouldBeCalledTimes(1);
    $tokens = $expired_collector->collectForAccount($account->reveal());
    $this->assertArrayEquals([1, 52], array_map(function ($entity) {
      return $entity->id();
    }, $tokens));
  }

  /**
   * @covers ::collect
   */
  public function testDeleteMultipleTokens() {
    list($expired_collector,, $storage) = $this->buildProphecies();
    $storage->delete(['foo'])->shouldBeCalledTimes(1);
    $expired_collector->deleteMultipleTokens(['foo']);
  }

  protected function buildProphecies() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);

    $token_storage = $this->prophesize(EntityStorageInterface::class);
    $token_query = $this->prophesize(QueryInterface::class);
    $token_query->execute()->willReturn([1 => '1', 52 => '52']);
    $token_storage->getQuery()->willReturn($token_query->reveal());
    $token1 = $this->prophesize(Oauth2Token::class);
    $token1->id()->willReturn(1);
    $token52 = $this->prophesize(Oauth2Token::class);
    $token52->id()->willReturn(52);
    $token_storage->loadMultiple(['1', '52'])->willReturn([
      1 => $token1->reveal(),
      52 => $token52->reveal(),
    ]);

    $client_storage = $this->prophesize(EntityStorageInterface::class);
    $client_query = $this->prophesize(QueryInterface::class);
    $client_query->execute()->willReturn([6 => '6']);
    $client_storage->getQuery()->willReturn($client_query->reveal());
    $client6 = $this->prophesize(Consumer::class);
    $client6->id()->willReturn(6);
    $client_storage->loadByProperties([
      'user_id' => 22,
    ])->willReturn([6 => $client6->reveal()]);

    $entity_type_manager->getStorage('oauth2_token')->willReturn($token_storage->reveal());
    $entity_type_manager->getStorage('consumer')->willReturn($client_storage->reveal());

    $date_time = $this->prophesize(TimeInterface::class);
    $date_time->getRequestTime()->willReturn(42);

    $expired_collector = new ExpiredCollector($entity_type_manager->reveal(), $date_time->reveal());

    return [
      $expired_collector,
      $token_query,
      $token_storage,
      $client_query,
      $client_storage,
    ];
  }

}
