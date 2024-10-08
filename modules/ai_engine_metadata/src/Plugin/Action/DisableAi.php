<?php

declare(strict_types=1);

namespace Drupal\ai_engine_metadata\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Disable AI action.
 *
 * @Action(
 *   id = "ai_engine_disable_ai",
 *   label = @Translation("Disable AI"),
 *   type = "node",
 *   category = @Translation("Custom"),
 * )
 *
 * @DCG
 * For updating entity fields consider extending FieldUpdateActionBase.
 * @see \Drupal\Core\Field\FieldUpdateActionBase
 *
 * @DCG
 * In order to set up the action through admin interface the plugin has to be
 * configurable.
 * @see https://www.drupal.org/project/drupal/issues/2815301
 * @see https://www.drupal.org/project/drupal/issues/2815297
 *
 * @DCG
 * The whole action API is subject of change.
 * @see https://www.drupal.org/project/drupal/issues/2011038
 */
class DisableAi extends ActionBase {

  const METATAG_FIELD_NAME = 'ai_disable_indexing';
  const ACTION_VALUE = 'disabled';
  const MANAGE_AI_PERMISSION = 'manage ai engine settings';

  /**
   * {@inheritdoc}
   */
  public function access($entity, ?AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface|bool {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $access = $entity->access('update', $account, TRUE)
      ->andIf(AccessResult::allowedIfHasPermission($account, self::MANAGE_AI_PERMISSION));
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(?ContentEntityInterface $entity = NULL): void {
    if (!$entity) {
      return;
    }

    if ($entity->hasField('field_metatags')) {
      $metaTagsArray = json_decode($entity->field_metatags->value ?? "{}", TRUE);
      $metaTagsArray[self::METATAG_FIELD_NAME] = self::ACTION_VALUE;
      $metaTagsJson = json_encode($metaTagsArray);
      $entity->field_metatags->value = $metaTagsJson;
      $entity->save();
    }
  }

}
