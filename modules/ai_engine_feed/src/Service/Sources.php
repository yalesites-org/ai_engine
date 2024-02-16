<?php

namespace Drupal\ai_engine_feed\Service;

use Drupal\ai_engine_metadata\AiMetadataManager;
use Drupal\Core\Datetime\DrupalDateTime;
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
   * AI Metadata Manager.
   *
   * @var \Drupal\ai_metadata\AiMetadataManager
   */
  protected $aiMetadataManager;

  /**
   * Number of records per page.
   *
   * @var int
   */
  const RECORDS_PER_PAGE = 50;

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
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->renderer = $renderer;
    $this->requestStack = $requestStack;
    $this->aiMetadataManager = $ai_metadata_manager;
  }

  /**
   * Retrieves and array of content for the AI feed.
   *
   * This method delivers all published nodes that are accessible to anonymous
   * users. In the future, this query can grow to include new filters and entity
   * types. This method also processes the content to put it into a consistent
   * and expected format.
   *
   * @return array
   *   An array of content data for the AI feed.
   */
  public function getContent($page = 1): array {

    $jsonReturn = [
      'data' => $this->getEntityData($page),
      'links' => $this->getApiLinks($page),
      'totals' => $this->getApiTotals(),
    ];

    return $jsonReturn;

  }

  /**
   * Retrieves entity data.
   *
   * @param int $page
   *   The current page to retrieve data from.
   *
   * @return array
   *   An array of content data for the AI feed.
   */
  protected function getEntityData($page) {
    // Get offset of records based on page.
    $offset = ($page - 1) * self::RECORDS_PER_PAGE;

    // Query to build a collection of content to be ingested.
    $query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->range($offset, self::RECORDS_PER_PAGE)
      ->accessCheck(TRUE);

    // Not including nodes that are marked to be excluded from the AI index.
    $andCondition = $query->orConditionGroup()
      ->condition('field_metatags', '%ai_disable_indexing%', 'NOT LIKE')
      ->condition('field_metatags', NULL, 'IS NULL');

    $query->condition($andCondition);

    $ids = $query->execute();
    $entities = $this->entityTypeManager->getStorage('node')->loadMultiple($ids);

    // Process the collection to fit the shape of the API.
    $entityData = [];
    foreach ($entities as $entity) {
      /** @var \Drupal\node\Entity\Node $entity */
      // Not including nodes that are marked to be excluded from the AI index.
      $entityData[] = [
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
    }
    return $entityData;
  }

  /**
   * Retrieves API links.
   *
   * @param int $page
   *   The current page to calculate page links.
   *
   * @return array
   *   An array of API links.
   */
  protected function getApiLinks($page) {
    $host = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
    $baseUrl = "{$host}/api/ai/v1/content?page=";
    $apiTotals = $this->getApiTotals();

    $prevPageLink = "";
    if ($apiTotals['total_pages'] > 1 && $page > 1 && $page <= $apiTotals['total_pages']) {
      $prevPage = $page - 1;
      $prevPageLink = $baseUrl . $prevPage;
    }

    $selfPageLink = "";
    if ($page <= $apiTotals['total_pages']) {
      $selfPageLink = $baseUrl . $page;
    }

    $nextPageLink = "";
    if ($apiTotals['total_pages'] > 1 && $page < $apiTotals['total_pages']) {
      $nextPage = $page + 1;
      $nextPageLink = $baseUrl . $nextPage;
    }

    $apiLinks = [
      'first' => $baseUrl . 1,
      'prev' => $prevPageLink,
      'self' => $selfPageLink,
      'next' => $nextPageLink,
      'last' => $baseUrl . $apiTotals['total_pages'],
    ];

    return $apiLinks;
  }

  /**
   * Retrieves API totals.
   *
   * @return array
   *   An array of API totals.
   */
  protected function getApiTotals() {
    // Total entities.
    $total = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(TRUE);

    // Remove nodes that are marked to be excluded from the AI index.
    $andCondition = $total->orConditionGroup()
      ->condition('field_metatags', '%ai_disable_indexing%', 'NOT LIKE')
      ->condition('field_metatags', NULL, 'IS NULL');

    $total->condition($andCondition);
    $ids = $total->execute();

    $entities = $this->entityTypeManager->getStorage('node')->loadMultiple($ids);
    $totalEntities = count($entities);
    $totalPages = ceil($totalEntities / self::RECORDS_PER_PAGE);

    $apiTotals = [
      'total_records' => $totalEntities,
      'total_pages' => $totalPages,
    ];

    return $apiTotals;
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
    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $renderArray = $view_builder->view($entity, 'default');
    return $this->renderer->render($renderArray);
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
  public function getSearchIndexId(EntityInterface $entity) {
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
  protected function getDocumentType(EntityInterface $entity) {
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
  protected function getUrl(EntityInterface $entity) {
    return $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
  }

}
