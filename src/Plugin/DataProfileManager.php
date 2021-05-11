<?php

namespace Drupal\dgi_actions\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Data profile plugin manager.
 */
class DataProfileManager extends DefaultPluginManager {

  /**
   * Constructs a new DataProfileManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/DataProfile', $namespaces, $module_handler, 'Drupal\dgi_actions\Plugin\DataProfileInterface', 'Drupal\dgi_actions\Annotation\DataProfile');

    $this->alterInfo('dgi_actions_data_profile_info');
    $this->setCacheBackend($cache_backend, 'dgi_actions_data_profile_plugins');
  }

}
