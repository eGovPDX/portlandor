<?php
namespace Drupal\portland_govdelivery\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\portland_govdelivery\Service\GovDeliveryClient;

/**
 * @QueueWorker(
 *   id = "govdelivery_subscribe",
 *   title = @Translation("GovDelivery Subscribe Worker"),
 *   cron = {"time" = 60}
 * )
 */
class GovDeliverySubscribeWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  protected GovDeliveryClient $client;
  protected int $maxAttempts = 5;
  protected int $backoffBaseSeconds = 60;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, GovDeliveryClient $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('portland_govdelivery.client')
    );
  }

  public function processItem($data) {
    if (!empty($data['next_attempt']) && time() < $data['next_attempt']) {
      \Drupal::queue('govdelivery_subscribe')->createItem($data);
      return;
    }

    $attempts = isset($data['attempts']) ? (int) $data['attempts'] : 0;
    $email = $data['email'] ?? NULL;
    $topics = $data['topics'] ?? [];
    if (empty($email)) {
      return;
    }

    try {
      $this->client->subscribeUser($email, $topics);
      return;
    }
    catch (\Throwable $e) {
      $attempts++;
      if ($attempts >= $this->maxAttempts) {
        \Drupal::logger('portland_govdelivery')->error('Giving up subscribing %email after %attempts attempts: @msg', ['%email' => $email, '%attempts' => $attempts, '@msg' => $e->getMessage()]);
        return;
      }

      $delay = $this->backoffBaseSeconds * (2 ** ($attempts - 1));
      $data['attempts'] = $attempts;
      $data['next_attempt'] = time() + $delay;
      \Drupal::queue('govdelivery_subscribe')->createItem($data);
      \Drupal::logger('portland_govdelivery')->warning('Requeued subscribing %email (attempt %attempts) after error: @msg', ['%email'=>$email, '%attempts'=>$attempts, '@msg'=>$e->getMessage()]);
      return;
    }
  }
}
