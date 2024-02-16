<?php

namespace Drupal\ai_engine\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides a controller for the AI Engine module admin pages.
 */
class AiEngineAdminController extends ControllerBase {

  /**
   * Returns a simple text response for the AI Engine admin page.
   *
   * @return array
   *   A renderable array containing the text response.
   */
  public function content() {
    return [
      '#markup' => $this->t('Admin AI Engine settings.'),
    ];
  }

}
