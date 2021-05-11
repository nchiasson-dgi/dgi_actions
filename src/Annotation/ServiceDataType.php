<?php

namespace Drupal\dgi_actions\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Service data type item annotation object.
 *
 * @see \Drupal\dgi_actions\Plugin\ServiceDataTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class ServiceDataType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
