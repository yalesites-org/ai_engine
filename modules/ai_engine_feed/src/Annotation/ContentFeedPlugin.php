<?php

namespace Drupal\ai_engine_feed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a content feed plugin annotation object.
 *
 * @Annotation
 */
class ContentFeedPlugin extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

}
