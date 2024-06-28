<?php

namespace Drupal\ai_engine_feed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Setting form for the AI Engine Feed module.
 */
class AiEngineFeedSettings extends ConfigFormBase {
  /**
   * Config name.
   *
   * @var string
   */
  const CONFIG_NAME = 'ai_engine_feed.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ai_engine_feed_settings';
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

    $form['metatags_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Metatags field'),
      '#default_value' => 'field_metatags',
      '#description' => $this->t('The field name for storing metatags related to AI.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Attempt to find the metatags field entered if they entered one.
    $metatags_field = $form_state->getValue('metatags_field');
    if (!empty($metatags_field)) {
      $field_storage = \Drupal::entityTypeManager()->getStorage('field_storage_config')->load('node.' . $metatags_field);
      if (empty($field_storage)) {
      $form_state->setErrorByName('metatags_field', $this->t('Field @field does not exist.', ['@field' => $metatags_field]));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(self::CONFIG_NAME)
      ->set('metatags_field', $form_state->getValue('metatags_field'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
