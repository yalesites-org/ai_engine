<?php

namespace Drupal\ai_engine_feed\Service;

use Drupal\ai_engine_feed\ApiLinkBuilderTrait;
use Drupal\ai_engine_metadata\AiMetadataManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The AI Feed Sources service.
 *
 * This service is used to query and prepare content for the JSON feed consumed
 * by a language model integration framework such as LangChain.
 */
class Sources {

  use ApiLinkBuilderTrait;

  /**
   * Number of records per page for a paged result.
   *
   * @var int
   */
  const RECORDS_PER_PAGE = 50;

  /**
   * Config name.
   *
   * @var string
   */
  const CONFIG_NAME = 'ai_engine_feed.settings';

  /**
   * Configuration for feeds.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   *  The configuration factory.
   */
  protected $configFactory;

  /**
   * AI Metadata Manager.
   *
   * @var \Drupal\ai_metadata\AiMetadataManager
   */
  protected $aiMetadataManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new Sources object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\ai_engine_metadata\AiMetadataManager $ai_metadata_manager
   *   The AI metadata manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerInterface $logger,
    RendererInterface $renderer,
    RequestStack $requestStack,
    AiMetadataManager $ai_metadata_manager,
    EntityFieldManagerInterface $entityFieldManager,
    ConfigFactory $configFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->renderer = $renderer;
    $this->requestStack = $requestStack;
    $this->aiMetadataManager = $ai_metadata_manager;
    $this->entityFieldManager = $entityFieldManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Retrieves an array of content for the AI feed.
   *
   * This method delivers an array describing all published nodes that are
   * accessible to anonymous users. This list can be filtered by a variety of
   * query parameters inclduing the ability to specify a specific node id.
   *
   * @param array $params
   *   An array of URL parameters for the current request.
   *
   * @return array
   *   An array of content data for the AI feed.
   */
  public function getContent(array $params = []): array {
    // Query and format a list of entity data.
    $ids = $this->queryEntities($params);
    $entityType = $params['entityType'] ?? 'node';
    $entities = $this->entityTypeManager->getStorage($entityType)->loadMultiple($ids);
    $entityData = [];
    foreach ($entities as $entity) {
      /** @var \Drupal\node\Entity\Node $entity */
      $entityData[] = $this->generateEntityData($entity, $entityType);
    }

    $totalRecords = $this->countTotalRecords($params);
    $totalPages = ceil($totalRecords / self::RECORDS_PER_PAGE);
    return [
      'data' => $entityData,
      'links' => [
        'first' => $this->getApiLinkFirst($params, $totalPages),
        'prev' => $this->getApiLinkPrevious($params, $totalPages),
        'self' => $this->getApiLinkSelf($params, $totalPages),
        'next' => $this->getApiLinkNext($params, $totalPages),
        'last' => $this->getApiLinkLast($params, $totalPages),
      ],
      'totals' => [
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
      ],
    ];
  }

  /**
   *
   */
  public function generateEntityData($entity, $entityType) {
    switch ($entityType) {
      case 'node':
        return [
          'id' => $this->getSearchIndexId($entity),
          'source' => 'drupal',
          'documentType' => $this->getDocumentType($entity),
          'documentId' => $entity->id(),
          'documentUrl' => $this->getUrl($entity),
          'documentTitle' => $entity->getTitle(),
          'documentContent' => $this->processContentBody($entity),
          'metaTags' => $this->aiMetadataManager->getAiMetadata($entity)['ai_tags'],
          'metaDescription' => $this->aiMetadataManager->getAiMetadata($entity)['ai_description'],
          'dateCreated' => $this->formatTimestamp($entity->getCreatedTime()),
          'dateModified' => $this->formatTimestamp($entity->getChangedTime()),
          'dateProcessed' => $this->formatTimestamp(time()),
        ];

      case 'media':
        $fileData = $entity->get('field_media_file')->first()->getValue();
        $fileTitle = $fileData['description'];
        $file = $entity->get('field_media_file')->entity;
        $fileUrl = $file->createFileUrl(FALSE);
        return [
          'id' => $this->getSearchIndexId($entity),
          'source' => 'drupal',
          'documentType' => $this->getDocumentType($entity),
          'documentId' => $entity->id(),
          'documentUrl' => $this->getUrl($entity),
          'documentTitle' => $fileTitle,
          'documentContent' => $fileUrl,
          'metaTags' => $this->aiMetadataManager->getAiMetadata($entity)['ai_tags'],
          'metaDescription' => $this->aiMetadataManager->getAiMetadata($entity)['ai_description'],
          'dateCreated' => $this->formatTimestamp($entity->getCreatedTime()),
          'dateModified' => $this->formatTimestamp($entity->getChangedTime()),
          'dateProcessed' => $this->formatTimestamp(time()),
        ];
    }
  }

  /**
   * Query entities.
   *
   * @param array $params
   *   An array of URL parameters for the current request.
   * @param bool $pager
   *   TRUE if the results should use a pager.
   *
   * @return array
   *   An array of node IDs for content entities filtered by query parameters.
   */
  protected function queryEntities(array $params, bool $pager = TRUE): array {
    // Query all publically available nodes.
    $query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(TRUE);

    // Optionally page results.
    if ($pager) {
      $page = $params['page'] ?? 1;
      $offset = ($page - 1) * self::RECORDS_PER_PAGE;
      $query->range($offset, self::RECORDS_PER_PAGE);
    }

    // Optional filter by node ID. Useful for updating a single item.
    if (!empty($params['entityType']) && $params['entityType'] == 'node' && !empty($params['id'])) {
      $query->condition('nid', $params['id']);
    }

    // Don't include nodes that are marked to be excluded in the AI metadata.
    $metatags_field = $this->configFactory->get(self::CONFIG_NAME)->get('metatags_field');
    if (!empty($metatags_field)) {
      $andCondition = $query->orConditionGroup()
        ->condition($metatags_field, '%ai_disable_indexing%', 'NOT LIKE')
        ->condition($metatags_field, NULL, 'IS NULL');
      $query->condition($andCondition);
    }

    return $query->execute();
  }

  /**
   * Determines if field_metatags exists.
   *
   * @param string $fieldToSearch
   *   The field to search for.
   *
   * @return bool
   *   Whether the field exists or not.
   */
  protected function doesFieldMetatagsExist($fieldToSearch) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', 'node');

    return array_key_exists($fieldToSearch, $definitions);
  }

  /**
   * Retrieves a count of all entities for this query.
   *
   * @param array $params
   *   An array of URL parameters for the current request.
   *
   * @return int
   *   A count of all records returned in the entity query.
   */
  protected function countTotalRecords(array $params): int {
    return (int) count($this->queryEntities($params, FALSE));
  }

  /**
   * Format timestamps to use the ISO-8601 standard.
   *
   * @param int $timestamp
   *   A UNIX timestamp.
   *
   * @return string
   *   The formatted value of the date.
   */
  protected function formatTimestamp(int $timestamp): string {
    $dateTime = DrupalDateTime::createFromTimestamp($timestamp);
    return $dateTime->format(\DateTime::ATOM);
  }

  /**
   * Processes the content body of a content entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  protected function processContentBody(EntityInterface $entity) {
    try {
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $renderArray = $view_builder->view($entity, 'default');
      $returnValue = $this->renderer->render($renderArray);
    } catch(\TypeError $e) {
      $returnValue = '';
    }

    return $returnValue;
  }

  /**
   * Get a unique ID to reference this item in the search index.
   *
   * Examples: "ask-yale-edu-node-14" or "hospitality-yale-edu-media-128".
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity.
   *
   * @return string
   *   A predictable and unique ID to reference this item in the search index.
   */
  public function getSearchIndexId(EntityInterface $entity): string {
    $host = $this->requestStack->getCurrentRequest()->getHttpHost();
    $host = preg_replace('/[^a-zA-Z0-9]+/', '-', $host);
    return $host . '-' . $entity->getEntityTypeId() . '-' . $entity->id();
  }

  /**
   * Gets a standardized document type.
   *
   * Document type is the name of the Drupal entity and possible bundle.
   * Examples: "node/post", "media/image", or "user".
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity.
   *
   * @return string
   *   A string representing the type of content.
   */
  protected function getDocumentType(EntityInterface $entity): string {
    $type = $entity->getEntityTypeId();
    if (!empty($entity->bundle())) {
      $type .= '/' . $entity->bundle();
    }
    return $type;
  }

  /**
   * Retrieves the canonical URL for a content entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity.
   *
   * @return string
   *   The canonical URL as a string.
   */
  protected function getUrl(EntityInterface $entity): string {
    return $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
  }

}
