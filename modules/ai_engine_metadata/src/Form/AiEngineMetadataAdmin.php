<?php

namespace Drupal\ai_engine_metadata\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin form for the AI Engine Metadata module.
 */
class AiEngineMetadataAdmin extends ConfigFormBase {

  /**
   * Config name.
   *
   * @var string
   */
  const CONFIG_NAME = 'ai_engine_metadata.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ai_engine_metadata_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable AI Metadata features'),
      '#default_value' => $config->get('enable') ?? FALSE,
      '#description' => $this->t('Enable or disable AI metadata tools across the site.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(self::CONFIG_NAME)
      ->set('enable', $form_state->getValue('enable'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
