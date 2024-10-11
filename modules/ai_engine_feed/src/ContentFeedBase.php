<?php

namespace Drupal\ai_engine_feed;

use Drupal\Component\Plugin\PluginBase;

/**
 *
 */
class ContentFeedBase extends PluginBase implements ContentFeedPluginInterface {
  /**
   * The plugin id.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The plugin definition.
   *
   * @var mixed
   */
  protected $pluginDefinition;

  /**
   * The configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function generateFeed($source, $entity): array | NULL {
    throw new \Exception('Not implemented');
  }

}
