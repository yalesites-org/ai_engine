<?php

namespace Drupal\ai_engine_feed\Plugin\ContentFeedPlugin;

use Drupal\ai_engine_feed\ContentFeedBase;

/**
 * Node Plugin for content feed.
 *
 * @ContentFeedPlugin(
 *  id = "node"
 * )
 */
class NodePlugin extends ContentFeedBase {

  /**
   * {@inheritdoc}
   */
  public function generateFeed($source, $entity): array | NULL {
    return [
      'id' => $source->getSearchIndexId($entity),
      'source' => 'drupal',
      'documentType' => $source->getDocumentType($entity),
      'documentId' => $entity->id(),
      'documentUrl' => $source->getUrl($entity),
      'documentTitle' => $entity->getTitle(),
      'documentContent' => $source->processContentBody($entity),
      'metaTags' => $source->getMetaTags($entity),
      'metaDescription' => $source->getMetaDescription($entity),
      'dateCreated' => $source->formatTimestamp($entity->getCreatedTime()),
      'dateModified' => $source->formatTimestamp($entity->getChangedTime()),
      'dateProcessed' => $source->formatTimestamp(time()),
    ];
  }

}
