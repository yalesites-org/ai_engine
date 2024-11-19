<?php

namespace Drupal\ai_engine_feed;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 *
 */
class ContentFeedManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ContentFeedPlugin',
      $namespaces,
      $module_handler,
      'Drupal\ai_engine_feed\ContentFeedPluginInterface',
      'Drupal\ai_engine_feed\Annotation\ContentFeedPlugin'
    );
    $this->alterInfo('ai_engine_feed');
    $this->setCacheBackend($cache_backend, 'ai_engine_feed');
  }

  /**
   * Given an entity type, find the proper plugin to use.
   *
   * @param string $entityType
   *   The entity type to find the plugin for.
   */
  public function getPluginIdFromEntityType($entityType) {
    $plugin_definitions = $this->getDefinitions();
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      if ($plugin_definition['id'] == $entityType) {
        return $plugin_id;
      }
    }

    return NULL;
  }

}
