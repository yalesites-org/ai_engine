<?php

namespace Drupal\ai_engine_metadata\Plugin\Action;

/**
 * Provides a Disable AI action for media.
 *
 * @Action(
 *  id = "ai_engine_disable_media_ai",
 *  label = @Translation("Disable AI for media"),
 *  type = "media",
 *  category = @Translation("Custom"),
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
final class DisableMediaAi extends DisableAi {
}
