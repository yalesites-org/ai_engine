<?php

namespace Drupal\ai_engine_chat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Setting form for the AI Engine Chat module.
 */
class AiEngineChatSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ai_engine_chat_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ai_engine_chat.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ai_engine_chat.settings');

    $form['enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable chat widget'),
      '#default_value' => $config->get('enable') ?? FALSE,
      '#description' => t('Enable or disable chat service across the site.'),
    );

    $form['azure_base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Azure base URL'),
      '#description' => $this->t('Ex: https://askyalewebapp.azurewebsites.net'),
      '#default_value' => $config->get('azure_base_url') ?? NULL,
    ];

    $form['initial_questions'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Initial question prompts'),
      '#cardinality' => 4,
      '#default_value' => ($config->get('initial_questions')) ?? [],
      '#description' => $this->t('A list of prompts to show when the chat is empty'),

      'question' => [
        '#type' => 'textfield',
        '#title' => $this->t('Question'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ai_engine_chat.settings')
      ->set('enable', $form_state->getValue('enable'))
      ->set('azure_base_url', $form_state->getValue('azure_base_url'))
      ->set('initial_questions', $form_state->getValue('initial_questions'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
