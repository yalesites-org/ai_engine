<?php

namespace Drupal\ai_engine_metadata;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Utility\Token;
use Drupal\metatag\MetatagManager;

/**
 * Service for managing the AI Metadata module.
 */
class AiMetadataManager {

  /**
   * The token class.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Metatag manager.
   *
   * @var \Drupal\metatag\MetatagManager
   */
  protected $metatagManager;

  /**
   * Constructs a new Sources object.
   *
   * @param \Drupal\metatag\MetatagManager $metatag_manager
   *   The metatag manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token class.
   */
  public function __construct(
    MetatagManager $metatag_manager,
    Token $token,
  ) {
    $this->metatagManager = $metatag_manager;
    $this->token = $token;
  }

  /**
   * Gets all custom AI metadata on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to retrieve metadata from.
   *
   * @return array
   *   Metadata for specified entity.
   */
  public function getAiMetadata(ContentEntityInterface $entity) {
    $tags = $this->metatagManager->tagsFromEntity($entity);
    $aiDesc = isset($tags['ai_description']) ? $this->token->replace($tags['ai_description'], [$entity->getEntityTypeId() => $entity]) : "";
    $aiTags = isset($tags['ai_tags']) ? strip_tags($this->token->replace($tags['ai_tags'], [$entity->getEntityTypeId() => $entity])) : "";
    $aiDisableIndex = isset($tags['ai_disable_indexing']) ? TRUE : FALSE;

    $metaData = [
      'ai_description' => $aiDesc,
      'ai_tags' => $aiTags,
      'ai_disable_index' => $aiDisableIndex,
    ];

    return $metaData;
  }

}
