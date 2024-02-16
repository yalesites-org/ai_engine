<?php

namespace Drupal\ai_engine_database\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityDeleteEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Logs a message when a node is deleted.
 */
class RemoveSubscriber implements EventSubscriberInterface {

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new AiDatabaseEventSubscriber object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerChannelInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityHookEvents::ENTITY_UPDATE][] = ['entityUpdate'];
    $events[EntityHookEvents::ENTITY_DELETE][] = ['entityDelete'];
    return $events;
  }

  /**
   * Entity update.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent $event
   *   The event.
   */
  public function entityUpdate(EntityUpdateEvent $event): void {
    $entity = $event->getEntity();
    if ($entity instanceof \Drupal\node\NodeInterface) {
      if($entity->original->isPublished() && !$entity->isPublished()) {
        $this->removeEntity($entity);
      }
    }
  }


  /**
   * Entity delete.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityDeleteEvent $event
   *   The event.
   */
  public function entityDelete(EntityDeleteEvent $event): void {
    $entity = $event->getEntity();
    if ($entity instanceof \Drupal\node\NodeInterface) {
      $this->removeEntity($entity);
    }
  }

  protected function removeEntity($entity) {
    // @todo: Remove node from the vector database.
    $this->logger->notice(
      'Remove node @nid from vector database.',
      ['@nid' => $entity->id()]
    );
  }

}
