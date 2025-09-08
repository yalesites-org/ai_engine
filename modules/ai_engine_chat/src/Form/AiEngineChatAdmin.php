<?php

namespace Drupal\ai_engine_chat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin form for the AI Engine Chat module.
 */
class AiEngineChatAdmin extends ConfigFormBase {

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
    return 'ai_engine_chat_admin';
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
    $form['floating_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable floating chat button'),
      '#default_value' => $config->get('floating_button') ?? FALSE,
    ];
    $form['floating_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Floating button text'),
      '#default_value' => $config->get('floating_button_text') ?? $this->t('Ask Yale Chat'),
      '#required' => TRUE,
    ];
    $form['azure_base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Azure base URL'),
      '#description' => $this->t('The base URL for your Azure deployment.'),
      '#default_value' => $config->get('azure_base_url') ?? NULL,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $config
      ->set('enable', $form_state->getValue('enable'))
      ->set('floating_button', $form_state->getValue('floating_button'))
      ->set('floating_button_text', $form_state->getValue('floating_button_text'))
      ->set('azure_base_url', $form_state->getValue('azure_base_url'));

    $config->save();
    parent::submitForm($form, $form_state);
  }

}