<?php

namespace Drupal\ai_engine_feed\Plugin\ContentFeedPlugin;

use Drupal\ai_engine_feed\ContentFeedBase;

/**
 * Media Plugin for Content Feed.
 *
 * @ContentFeedPlugin(
 *   id = "media"
 * )
 */
class MediaPlugin extends ContentFeedBase {

  /**
   * {@inheritdoc}
   */
  public function generateFeed($source, $entity): array | NULL {
    $fileData = $entity->get('field_media_file')->first()->getValue();
    $fileTitle = $fileData['description'];
    $file = $entity->get('field_media_file')->entity;
    $fileUrl = $file->createFileUrl(FALSE);
    return [
      'id' => $source->getSearchIndexId($entity),
      'source' => 'drupal',
      'documentType' => $source->getDocumentType($entity),
      'documentId' => $entity->id(),
      'documentUrl' => $source->getUrl($entity),
      'documentTitle' => $fileTitle,
      'documentContent' => $fileUrl,
      'metaTags' => $source->getMetaTags($entity),
      'metaDescription' => $source->getMetaDescription($entity),
      'dateCreated' => $source->formatTimestamp($entity->getCreatedTime()),
      'dateModified' => $source->formatTimestamp($entity->getChangedTime()),
      'dateProcessed' => $source->formatTimestamp(time()),
    ];
  }

  /**
   *
   */
  protected function getFileDataField($entity) {
    $possibilities = ['field_media_file', 'field_media_image'];

    foreach ($possibilities as $field) {
      if ($entity->hasField($field)) {
        return $entity->get($field);
      }
    }

    return NULL;
  }

}
