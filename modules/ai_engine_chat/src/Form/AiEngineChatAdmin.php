<?php

namespace Drupal\ai_engine_chat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * Constructs an AiEngineChatAdmin object.
   *
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository.
   */
  public function __construct(KeyRepositoryInterface $key_repository) {
    $this->keyRepository = $key_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('key.repository')
    );
  }

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

    // System Instructions API Settings (only visible to users with
    // 'administer ai engine' permission).
    if ($this->currentUser()->hasPermission('administer ai engine')) {
      $form['system_instructions'] = [
        '#type' => 'details',
        '#title' => $this->t('System Instructions API Settings'),
        '#description' => $this->t('Configure the external API for managing system instructions.'),
        '#open' => FALSE,
      ];

      $form['system_instructions']['system_instructions_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable System Instruction Modification'),
        '#description' => $this->t('Allow users to modify system instructions via the API. When disabled, the system instructions management interface will be hidden.'),
        '#default_value' => $config->get('system_instructions_enabled') ?? FALSE,
      ];

      $form['system_instructions']['system_instructions_api_endpoint'] = [
        '#type' => 'url',
        '#title' => $this->t('API Endpoint'),
        '#description' => $this->t('The URL endpoint for the system instructions API.'),
        '#default_value' => $config->get('system_instructions_api_endpoint'),
        '#states' => [
          'required' => [
            ':input[name="system_instructions_enabled"]' => ['checked' => TRUE],
          ],
          'visible' => [
            ':input[name="system_instructions_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['system_instructions']['system_instructions_web_app_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Web App Name'),
        '#description' => $this->t('The web app name/index used in API calls.'),
        '#default_value' => $config->get('system_instructions_web_app_name') ?? '',
        '#states' => [
          'required' => [
            ':input[name="system_instructions_enabled"]' => ['checked' => TRUE],
          ],
          'visible' => [
            ':input[name="system_instructions_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];

      // Get available keys for the dropdown.
      $key_options = [];
      $keys = $this->keyRepository->getKeys();
      foreach ($keys as $key) {
        $key_options[$key->id()] = $key->label();
      }

      $form['system_instructions']['system_instructions_api_key'] = [
        '#type' => 'select',
        '#title' => $this->t('API Key'),
        '#description' => $this->t('Select the key to use for API authentication. Keys are managed in the Key module.'),
        '#options' => $key_options,
        '#default_value' => $config->get('system_instructions_api_key') ?? 'AI_CHAT_API_KEY',
        '#empty_option' => $this->t('- Select a key -'),
        '#states' => [
          'required' => [
            ':input[name="system_instructions_enabled"]' => ['checked' => TRUE],
          ],
          'visible' => [
            ':input[name="system_instructions_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['system_instructions']['system_instructions_max_length'] = [
        '#type' => 'number',
        '#title' => $this->t('Maximum Instructions Length'),
        '#description' => $this->t('The recommended maximum character length for system instructions. Users can exceed this limit, but will receive a performance warning.'),
        '#default_value' => $config->get('system_instructions_max_length') ?? 4000,
        '#min' => 100,
        '#max' => 50000,
        '#required' => TRUE,
      ];

      $form['system_instructions']['system_instructions_warning_threshold'] = [
        '#type' => 'number',
        '#title' => $this->t('Warning Threshold'),
        '#description' => $this->t('Character count at which to show a warning to users as they approach the maximum length.'),
        '#default_value' => $config->get('system_instructions_warning_threshold') ?? 3500,
        '#min' => 100,
        '#max' => 50000,
        '#required' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Only validate system instructions settings if the feature is enabled.
    if ($this->currentUser()->hasPermission('administer ai engine') && $form_state->getValue('system_instructions_enabled')) {
      if (empty($form_state->getValue('system_instructions_api_endpoint'))) {
        $form_state->setErrorByName('system_instructions_api_endpoint', $this->t('API Endpoint is required when system instruction modification is enabled.'));
      }

      if (empty($form_state->getValue('system_instructions_web_app_name'))) {
        $form_state->setErrorByName('system_instructions_web_app_name', $this->t('Web App Name is required when system instruction modification is enabled.'));
      }

      if (empty($form_state->getValue('system_instructions_api_key'))) {
        $form_state->setErrorByName('system_instructions_api_key', $this->t('API Key is required when system instruction modification is enabled.'));
      }

      // Validate character limits.
      $max_length = $form_state->getValue('system_instructions_max_length');
      $warning_threshold = $form_state->getValue('system_instructions_warning_threshold');
      
      if ($warning_threshold >= $max_length) {
        $form_state->setErrorByName('system_instructions_warning_threshold', $this->t('Warning threshold must be less than the maximum length.'));
      }
    }
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

    // Save system instructions API settings if user has permission.
    if ($this->currentUser()->hasPermission('administer ai engine')) {
      $config->set('system_instructions_enabled', $form_state->getValue('system_instructions_enabled'));

      // Only save the API settings if the feature is enabled.
      if ($form_state->getValue('system_instructions_enabled')) {
        $config
          ->set('system_instructions_api_endpoint', $form_state->getValue('system_instructions_api_endpoint'))
          ->set('system_instructions_web_app_name', $form_state->getValue('system_instructions_web_app_name'))
          ->set('system_instructions_api_key', $form_state->getValue('system_instructions_api_key'));
      }

      // Always save character limits (they apply regardless of API enablement).
      $config
        ->set('system_instructions_max_length', $form_state->getValue('system_instructions_max_length'))
        ->set('system_instructions_warning_threshold', $form_state->getValue('system_instructions_warning_threshold'));
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
