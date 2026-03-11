<?php

declare(strict_types=1);

namespace Drupal\portland\Logger\Handler;

use Drupal\monolog\Logger\ConditionResolver\ConditionResolverInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Adapter that allows the monolog ConditionalHandler to work with any HandlerInterface.
 *
 * Wraps the Drupal monolog ConditionalHandler to accept any HandlerInterface
 * (like GroupHandler), by adapting them to AbstractProcessingHandler internally.
 */
class ConditionalHandlerAdapter extends AbstractProcessingHandler
{

  private \Drupal\monolog\Logger\Handler\ConditionalHandler $handler;

  /**
   * ConditionalHandlerAdapter constructor.
   *
   * @param \Monolog\Handler\HandlerInterface $first
   *   The handler to use when the condition resolver returns TRUE.
   * @param \Monolog\Handler\HandlerInterface $second
   *   The handler to use when the condition resolver returns FALSE.
   * @param \Drupal\monolog\Logger\ConditionResolver\ConditionResolverInterface $conditionResolver
   *   The condition resolver.
   * @param int|string|\Monolog\Level $level
   *   The minimum logging level at which this handler will be triggered.
   * @param bool $bubble
   *   Whether the messages that are handled can bubble up the stack or not.
   */
  public function __construct(
    HandlerInterface $first,
    HandlerInterface $second,
    private readonly ConditionResolverInterface $conditionResolver,
    int|string|Level $level = Level::Debug,
    bool $bubble = TRUE,
  ) {
    parent::__construct($level, $bubble);

    // Adapt any HandlerInterface to work with ConditionalHandler by routing through write()
    $adaptedFirst = $this->adaptHandler($first);
    $adaptedSecond = $this->adaptHandler($second);

    // Create the underlying monolog ConditionalHandler
    $this->handler = new \Drupal\monolog\Logger\Handler\ConditionalHandler(
      $adaptedFirst,
      $adaptedSecond,
      $conditionResolver,
      $level,
      $bubble
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function write(LogRecord $record): void
  {
    $this->handler->handle($record);
  }

  /**
   * Adapt a HandlerInterface to AbstractProcessingHandler.
   *
   * @param \Monolog\Handler\HandlerInterface $handler
   *   The handler to adapt.
   *
   * @return \Monolog\Handler\AbstractProcessingHandler
   *   The adapted handler.
   */
  private function adaptHandler(HandlerInterface $handler): AbstractProcessingHandler
  {
    if ($handler instanceof AbstractProcessingHandler) {
      return $handler;
    }

    // Wrap non-AbstractProcessingHandler in an adapter
    return new class($handler) extends AbstractProcessingHandler {
      public function __construct(private HandlerInterface $wrappedHandler) {
        parent::__construct();
      }

      protected function write(LogRecord $record): void
      {
        $this->wrappedHandler->handle($record);
      }
    };
  }
}
