<?php

namespace Drupal\ai_engine_embedding\Service;

use Drupal\ai_engine_feed\Service\Sources;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\metatag\MetatagManager;

/**
 * Service for updating the vector database as content is updated.
 */
class EntityUpdate {

  const AZURE_SERVICE_NAME = 'yalehospitalitye2dev';
  const AZURE_INDEX_NAME = 'askyalehealth';

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The metatag manager service.
   *
   * @var \Drupal\metatag\MetatagManager
   */
  protected $metatagManager;

  /**
   * The AI Feed Sources service.
   *
   * @var \Drupal\ai_engine_feed\Service\Sources
   */
  protected $sources;

  /**
   * Constructs a new EntityUpdate object.
   *
   * @param \Drupal\ai_engine_feed\Service\Sources $sources
   *   The AI Feed Sources service.
   * @param \Drupal\Core\Http\ClientFactory $httpClientFactory
   *   The HTTP client factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   * @param \Drupal\metatag\MetatagManagerInterface $metatagManager
   *   The metatag manager service.
   */
  public function __construct(
    Sources $sources,
    ClientFactory $httpClientFactory,
    LoggerChannelInterface $logger,
    MetatagManager $metatagManager
  ) {
    $this->sources = $sources;
    $this->httpClientFactory = $httpClientFactory;
    $this->logger = $logger;
    $this->metatagManager = $metatagManager;
  }

  /**
   * Insert entity event.
   *
   * Used to add a new content entity to the vector database. This method fires
   * on hook_entity_insert(). Content must be publically visible and not blocked
   * from AI search indexing to be included in the vector database.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity in Drupal.
   */
  public function insert(EntityInterface $entity) {
    if (!$this->isIndexable($entity)) {
      return;
    }
    $this->upsertDocument($entity);
  }

  /**
   * Update entity event.
   *
   * Used to update the vector database when a content entity is changed. This
   * method fires on hook_entity_update(). Content may be updated or removed in
   * the vector database depending on how it was changed in Drupal.
   *
   * @todo This method does not check if the content is indexed before trying to
   * delete it. Ideally we would check $this->isIndexable($entity->original) to
   * determine the previous indexing state but this is not working for metatags.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity in Drupal.
   */
  public function update(EntityInterface $entity) {
    if (!$this->isSupportedEntityType($entity)) {
      return;
    }
    elseif (!$this->isIndexable($entity)) {
      $this->removeDocument($entity);
    }
    else {
      $this->upsertDocument($entity);
    }
  }

  /**
   * Delete entity event.
   *
   * Used to remove content from the vector database when it has been deleted
   * from Drupal. This method fires on hook_entity_delete().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity in Drupal.
   */
  public function delete(EntityInterface $entity) {
    if (!$this->isSupportedEntityType($entity)) {
      return;
    }
    $this->removeDocument($entity);
  }

  /**
   * Upsert document in vector database.
   *
   * Used to insert or update content into the vector database when it has been
   * added or updated in Drupal. This method fires on hook_entity_insert() and
   * hook_entity_update().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity in Drupal.
   */
  public function upsertDocument(EntityInterface $entity) {
    $route_params = [
      'entityType' => $entity->getEntityTypeId(),
      'id' => $entity->id(),
    ];
    $data = [
      "action" => "upsert",
      "service_name" => self::AZURE_SERVICE_NAME,
      "index_name" => self::AZURE_INDEX_NAME,
      "data" => $this->sources->getContentEndpoint($route_params),
    ];
    $httpClient = $this->httpClientFactory->fromOptions([
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ]);
    $endpoint = 'https://askyaleindexfunc.azurewebsites.net/api/upsert';

    try {
      $response = $httpClient->post($endpoint, ['json' => $data]);

      if ($response->getStatusCode() === 200) {
        $responseData = json_decode($response->getBody()->getContents(), TRUE);
        $this->logger->notice(
          'Removed node @id from vector database. Service response: @response',
          ['@id' => $entity->id(), '@response' => print_r($responseData, TRUE)]
        );
      }
      else {
        $this->logger->notice(
          'Unable to remove node @id from vector database. POST failed with status code: @code',
          ['@id' => $entity->id(), '@code' => $response->getStatusCode()]
        );
        return NULL;
      }
    }
    catch (\Exception $e) {
      $this->logger->error(
        'An error occurred while upserting document: @error',
        ['@error' => $e->getMessage()]
      );
      return NULL;
    }
  }

  /**
   * Remove document from vector database.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity in Drupal.
   */
  protected function removeDocument(EntityInterface $entity) {
    $data = [
      "action" => "delete",
      "service_name" => self::AZURE_SERVICE_NAME,
      "index_name" => self::AZURE_INDEX_NAME,
      "id_list" => [""],
      "id_filter_list" => [$this->sources->getSearchIndexId($entity)],
    ];
    $httpClient = $this->httpClientFactory->fromOptions([
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ]);
    $endpoint = 'https://askyaleindexfunc.azurewebsites.net/api/deletebyid';

    try {
      $response = $httpClient->post($endpoint, ['json' => $data]);

      if ($response->getStatusCode() === 200) {
        $responseData = json_decode($response->getBody()->getContents(), TRUE);
        $this->logger->notice(
          'Removed node @id from vector database. Service response: @response',
          ['@id' => $entity->id(), '@response' => print_r($responseData, TRUE)]
        );
      }
      else {
        $this->logger->notice(
          'Unable to remove node @id from vector database. POST failed with status code: @code',
          ['@id' => $entity->id(), '@code' => $response->getStatusCode()]
        );
        return NULL;
      }
    }
    catch (\Exception $e) {
      $this->logger->error(
        'An error occurred while deleting document: @error',
        ['@error' => $e->getMessage()]
      );
      return NULL;
    }
  }

  /**
   * Checks if an entity settings allow it to be indexed.
   *
   * Only publically visible content may be indexed. Unpublished content or
   * pages with custom access control may be excluded from the vector database.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity is allowed to be indexed, FALSE otherwise.
   */
  protected function isIndexable(EntityInterface $entity) {
    return $this->isSupportedEntityType($entity)
      && $this->isPubliclyViewable($entity)
      && $this->isIndexingEnabled($entity);
  }

  /**
   * Checks if an entity is supported by the embedding system.
   *
   * @todo Currently this service only supports nodes. Consider adding an admin
   * form to manage which types and bundles support indexing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity should be embedded, FALSE otherwise.
   */
  protected function isSupportedEntityType(EntityInterface $entity) {
    return $entity->getEntityTypeId() === 'node';
  }

  /**
   * Checks if an entity is publicly viewable.
   *
   * Unpublished content or pages with restrictive access controls are excluded
   * from the vector databases.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity should be embedded, FALSE otherwise.
   */
  protected function isPubliclyViewable(EntityInterface $entity) {
    if ($entity instanceof EntityPublishedInterface) {
      return $entity->isPublished();
    }
    return TRUE;
  }

  /**
   * Checks if the entity AI Metadata has indexing enabled.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   FALSE if indexing is disabled in metatags, otherwise TRUE.
   */
  public function isIndexingEnabled(EntityInterface $entity) {
    $tags = $this->metatagManager->tagsFromEntityWithDefaults($entity);
    $metatags = $this->metatagManager->generateTokenValues($tags, $entity);
    $key = 'ai_disable_indexing';
    if (isset($metatags[$key]) && $metatags[$key] == 'disabled') {
      return FALSE;
    }
    return TRUE;
  }

}
