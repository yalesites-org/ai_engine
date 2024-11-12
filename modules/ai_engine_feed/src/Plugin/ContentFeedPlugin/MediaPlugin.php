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
    $titleFields = ['description', 'title', 'alt'];
    $fileDataField = $this->getFileDataField($entity);
    if (!$fileDataField) {
      throw new \Exception('No file data field found.');
    }

    $entityTitle = $entity->label();
    $fileData = $fileDataField->first()->getValue();
    $fileTitle = '';
    foreach ($titleFields as $field) {
      if (isset($fileData[$field])) {
        $fileTitle = $fileData[$field];
        break;
      }
    }
    $file = $fileDataField->entity;
    $fileUrl = $file->createFileUrl(FALSE);
    return [
      'id' => $source->getSearchIndexId($entity),
      'source' => 'drupal',
      'documentType' => $source->getDocumentType($entity),
      'documentId' => $entity->id(),
      'documentUrl' => $fileUrl,
      'documentTitle' => $entityTitle,
      'documentContent' => '',
      'documentDescription' => $fileTitle,
      'metaTags' => $source->getMetaTags($entity),
      'metaDescription' => $source->getMetaDescription($entity),
      'dateCreated' => $source->formatTimestamp($entity->getCreatedTime()),
      'dateModified' => $source->formatTimestamp($entity->getChangedTime()),
      'dateProcessed' => $source->formatTimestamp(time()),
    ];
  }

  /**
   * Get the file data field since it can be multiple names.
   *
   * @param object $entity
   *   The entity object.
   *
   * @return object|null
   *   The file data field.
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
