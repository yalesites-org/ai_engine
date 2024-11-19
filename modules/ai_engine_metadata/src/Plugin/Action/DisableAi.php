<?php

declare(strict_types=1);

namespace Drupal\ai_engine_metadata\Plugin\Action;

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
class DisableAi extends MetatagValueSetAction {
  /**
   * {@inheritdoc}
   */
  protected static $entityMetatagFieldName = 'ai_disable_indexing';

  /**
   * {@inheritdoc}
   */
  protected static $metatagFieldName = 'field_metatags';

  /**
   * {@inheritdoc}
   */
  protected static $actionValue = 'disabled';

}
