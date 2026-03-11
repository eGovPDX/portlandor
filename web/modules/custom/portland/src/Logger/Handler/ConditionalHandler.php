<?php

declare(strict_types=1);

namespace Drupal\portland\Logger\Handler;

use Drupal\monolog\Logger\ConditionResolver\ConditionResolverInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Conditional handler that routes records to one of two handlers based on a condition.
 *
 * Unlike the default ConditionalHandler in the Monolog module, this accepts 
 * any HandlerInterface, allowing it to work with GroupHandler and other handler types.
 */
class ConditionalHandler implements HandlerInterface
{

  /**
   * ConditionalHandler constructor.
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
    private readonly HandlerInterface $first,
    private readonly HandlerInterface $second,
    private readonly ConditionResolverInterface $conditionResolver,
    private readonly int|string|Level $level = Level::Debug,
    private readonly bool $bubble = TRUE,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function isHandling(LogRecord $record): bool
  {
    $level = $record->level;
    if (is_int($this->level)) {
      $minLevel = $this->level;
    } else {
      $minLevel = $this->level instanceof Level ? $this->level->value : Level::from($this->level)->value;
    }

    if ($level->value < $minLevel) {
      return FALSE;
    }

    if ($this->conditionResolver->resolve()) {
      return $this->first->isHandling($record);
    }
    else {
      return $this->second->isHandling($record);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handle(LogRecord $record): bool
  {
    $level = $record->level;
    if (is_int($this->level)) {
      $minLevel = $this->level;
    } else {
      $minLevel = $this->level instanceof Level ? $this->level->value : Level::from($this->level)->value;
    }

    if ($level->value < $minLevel) {
      return FALSE;
    }

    if ($this->conditionResolver->resolve()) {
      $result = $this->first->handle($record);
    }
    else {
      $result = $this->second->handle($record);
    }

    return FALSE === $this->bubble ? TRUE : $result;
  }

  /**
   * {@inheritdoc}
   */
  public function handleBatch(array $records): void
  {
    foreach ($records as $record) {
      $this->handle($record);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function close(): void
  {
    $this->first->close();
    $this->second->close();
  }

  /**
   * Push a processor on to the handler stack.
   *
   * @param callable $callback
   *   The processor callback.
   *
   * @return self
   *   This handler instance.
   */
  public function pushProcessor(callable $callback): static
  {
    // Note: This handler doesn't support processors directly since it's
    // just a router. Processors should be added to the child handlers.
    return $this;
  }

  /**
   * Pop a processor off the handler stack.
   *
   * @return callable
   *   The popped processor.
   */
  public function popProcessor(): callable
  {
    throw new \LogicException('There are no processors to pop');
  }

}
