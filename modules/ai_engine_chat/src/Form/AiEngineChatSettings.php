<?php

namespace Drupal\ai_engine_chat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Setting form for the AI Engine Chat module.
 */
class AiEngineChatSettings extends ConfigFormBase {

  /**
   * Config name.
   *
   * @var string
   */
  const CONFIG_NAME = 'ai_engine_chat.settings';

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
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable chat widget'),
      '#default_value' => $config->get('enable') ?? FALSE,
      '#description' => $this->t('Enable or disable chat service across the site. Chat can be launched by using the href="#launch-chat" on any link.'),
    ];
    $form['azure_base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Azure base URL'),
      '#description' => $this->t('Ex: https://askyalehealth.azurewebsites.net'),
      '#default_value' => $config->get('azure_base_url') ?? NULL,
    ];
    $form['prompts'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Initial Prompts'),
      '#description' => $this->t('A list of example prompts to show when the chat is initially launched'),
      '#tree' => TRUE,
    ];
    for ($i = 0; $i < 4; $i++) {
      $form['prompts'][$i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Prompt '),
        '#default_value' => $config->get('prompts')[$i] ?? [],
      ];
    }
    $form['disclaimer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disclaimer'),
      '#description' => $this->t('Appears below the chat form. No markup allowed, max of about 100 characters'),
      '#default_value' => $config->get('disclaimer') ?? NULL,
      '#rows' => 2,
    ];
    $form['footer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Footer'),
      '#description' => $this->t('Displays at the bottom of the modal window. May include links and basic HTML.'),
      '#default_value' => $config->get('footer') ?? NULL,
      '#rows' => 2,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(self::CONFIG_NAME)
      ->set('enable', $form_state->getValue('enable'))
      ->set('azure_base_url', $form_state->getValue('azure_base_url'))
      ->set('prompts', array_values(array_filter($form_state->getValue('prompts'))))
      ->set('disclaimer', $form_state->getValue('disclaimer'))
      ->set('footer', $form_state->getValue('footer'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
