<?php

declare(strict_types=1);

namespace Drupal\ai_engine_metadata\Plugin\Action;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an Enable AI action.
 *
 * @Action(
 *   id = "ai_engine_enable_ai",
 *   label = @Translation("Enable AI"),
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
final class EnableAi extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($entity, ?AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface|bool {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $access = $entity->access('update', $account, TRUE);
    /*->andIf($entity->metatag->ai_disable_indexing->access('edit', $account, TRUE));*/
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(?ContentEntityInterface $entity = NULL): void {
    $entity->metatag->ai_disable_indexing = '';
    $entity->save();
  }

}
