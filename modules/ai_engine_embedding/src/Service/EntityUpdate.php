<?php

namespace Drupal\ai_engine_embedding\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ai_engine_feed\Service\Sources;
use Drupal\metatag\MetatagManager;

/**
 * Service for updating the vector database as content is updated.
 */
class EntityUpdate {
  const CHUNK_SIZE_DEFAULT = 3000;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   * @param \Drupal\metatag\MetatagManagerInterface $metatagManager
   *   The metatag manager service.
   */
  public function __construct(
    Sources $sources,
    ClientFactory $httpClientFactory,
    ConfigFactoryInterface $configFactory,
    LoggerChannelInterface $logger,
    MetatagManager $metatagManager,
  ) {
    $this->sources = $sources;
    $this->httpClientFactory = $httpClientFactory;
    $this->configFactory = $configFactory;
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
    if (!$this->isServiceEnabled() || !$this->isIndexable($entity)) {
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
    if (!$this->isServiceEnabled() || !$this->isSupportedEntityType($entity)) {
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
    if (!$this->isServiceEnabled() || !$this->isSupportedEntityType($entity)) {
      return;
    }
    $this->removeDocument($entity);
  }

  /**
   * Add all document to the vector databased.
   *
   * Used to add all existing documents. This is triggered in the AI Engine
   * Embedding Settings form. It is a useful tool when adding AskYale to a new
   * website where we want to index all existing content.
   *
   * Note: This method does not delete or modify any existing documents in the
   * search index. Previously existing document may be updated but there is not
   * a cleanup routine to find and delete out of date chunks.
   */
  public function addAllDocuments() {
    $config = $this->configFactory->get('ai_engine_embedding.settings');
    $data = $this->getData("upsert", $config, [], "");
    $httpClient = $this->httpClientFactory->fromOptions([
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ]);
    $endpoint = $config->get('azure_embedding_service_url') . '/api/upsert';

    try {
      $response = $httpClient->post($endpoint, ['json' => $data]);

      if ($response->getStatusCode() === 200) {
        $responseData = json_decode($response->getBody()->getContents(), TRUE);
        $this->logger->notice(
          'Removed node @id from vector database. Service response: @response',
          ['@response' => print_r($responseData, TRUE)]
        );
      }
      else {
        $this->logger->notice(
          'Unable to remove node @id from vector database. POST failed with status code: @code',
          ['@code' => $response->getStatusCode()]
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
    $config = $this->configFactory->get('ai_engine_embedding.settings');
    $chunk_size = $config->get('azure_chunk_size') || CHUNK_SIZE_DEFAULT;
    $route_params = [
      'entityType' => $entity->getEntityTypeId(),
      'id' => $entity->id(),
    ];
    $data = $this->getData("upsert", $config, $route_params, "");
    $httpClient = $this->httpClientFactory->fromOptions([
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ]);
    $endpoint = $config->get('azure_embedding_service_url') . '/api/upsert';

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
    $config = $this->configFactory->get('ai_engine_embedding.settings');
    $data = [
      "action" => "delete",
      "service_name" => $config->get('azure_search_service_name'),
      "index_name" => $config->get('azure_search_service_index'),
      "id_list" => [],
      "id_filter_list" => [$this->sources->getSearchIndexId($entity)],
    ];
    $httpClient = $this->httpClientFactory->fromOptions([
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ]);
    $endpoint = $config->get('azure_embedding_service_url') . '/api/deletebyid';

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
   * Checks if the embedding service is enabled.
   *
   * @return bool
   *   TRUE if the embedding service is enabled, FALSE otherwise.
   */
  protected function isServiceEnabled(): bool {
    return (bool) $this->configFactory
      ->get('ai_engine_embedding.settings')
      ->get('enable');
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
    $allowed_entities = ['node', 'media'];

    return in_array($entity->getEntityTypeId(), $allowed_entities);
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

  /**
   * Get the data to send to the AI Embedding service.
   *
   * @param string $action
   *   The action to perform on the data.
   * @param object $config
   *   The configuration object.
   * @param array $route_params
   *   An array of route parameters.
   * @param string $data
   *   The data to send to the AI Embedding service.
   *
   * @return array
   *   An array of data to send to the AI Embedding service.
   */
  protected function getData($action = 'upsert', $config, $route_params = [], $data = ""): array {
    $allowed_actions = ['upsert'];
    if (!$config) {
      throw new \Exception('Missing configuration object.');
    }

    if (!in_array($action, $allowed_actions)) {
      throw new \Exception('Invalid action provided.');
    }

    $chunk_size = $config->get('azure_chunk_size') ?? CHUNK_SIZE_DEFAULT;

    $data_endpoint = "";
    if ($data == "") {
      $data_endpoint = $this->sources->getContentEndpoint($route_params);
    }

    return [
      "action" => $action,
      "doctype" => "text",
      "service_name" => $config->get('azure_search_service_name'),
      "index_name" => $config->get('azure_search_service_index'),
      "data" => $data,
      "data_endpoint" => $data_endpoint,
      "chunk_size" => $chunk_size,
    ];
  }

}
