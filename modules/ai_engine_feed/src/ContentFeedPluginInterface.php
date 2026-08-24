<?php

namespace Drupal\ai_engine_feed;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Content Feed Data.
 */
interface ContentFeedPluginInterface extends PluginInspectionInterface {

  /**
   * Generate a content feed.
   *
   * @param \Drupal\ai_engine_feed\Service\Sources $source
   *   The source to generate a feed for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate a feed for.
   *
   * @return array
   *   The payload data to send on
   */
  public function generateFeed($source, $entity): array | NULL;

}
