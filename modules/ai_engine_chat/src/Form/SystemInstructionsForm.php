<?php

namespace Drupal\ai_engine_chat\Form;

use Drupal\ai_engine_chat\Service\SystemInstructionsManagerService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Form for managing system instructions.
 */
class SystemInstructionsForm extends FormBase {

  /**
   * The system instructions manager.
   *
   * @var \Drupal\ai_engine_chat\Service\SystemInstructionsManagerService
   */
  protected $instructionsManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a SystemInstructionsForm.
   *
   * @param \Drupal\ai_engine_chat\Service\SystemInstructionsManagerService $instructions_manager
   *   The system instructions manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(SystemInstructionsManagerService $instructions_manager, ConfigFactoryInterface $config_factory) {
    $this->instructionsManager = $instructions_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ai_engine_chat.system_instructions_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ai_engine_chat_system_instructions_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check if the feature is enabled.
    $config = $this->configFactory->get('ai_engine_chat.settings');
    if (!$config->get('system_instructions_enabled')) {
      throw new AccessDeniedHttpException('System instruction modification is not enabled.');
    }

    // Get current instructions and sync status.
    $current = $this->instructionsManager->getCurrentInstructions();
    $stats = $this->instructionsManager->getVersionStats();

    $form['#attached']['library'][] = 'ai_engine_chat/system_instructions';

    // Display sync status and version info.
    $form['status'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['system-instructions-status']],
    ];

    if (!$current['synced']) {
      $form['status']['sync_warning'] = [
        '#type' => 'item',
        '#markup' => $this->t('<div class="messages messages--warning">Warning: Could not sync with API: @error</div>', [
          '@error' => $current['sync_error'],
        ]),
      ];
    }

    $form['status']['info'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p><strong>Current version:</strong> @version | <strong>Total versions:</strong> @total | <a href="@history_url">View version history</a></p>', [
        '@version' => $current['version'] ?: $this->t('None'),
        '@total' => $stats['total_versions'],
        '@history_url' => Url::fromRoute('ai_engine_chat.system_instructions_versions')->toString(),
      ]),
    ];

    $form['instructions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('System Instructions'),
      '#description' => $this->t('Enter the system instructions for the AI chat. Recommended maximum length: 4,000 characters.'),
      '#default_value' => $current['instructions'],
      '#rows' => 15,
      '#maxlength' => NULL,
      '#attributes' => [
        'data-maxlength' => 4000,
        'data-maxlength-warning-class' => 'warning',
        'data-maxlength-limit-reached-class' => 'error',
      ],
      '#required' => TRUE,
    ];

    $form['notes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Version Notes'),
      '#description' => $this->t('Optional notes about this version (e.g., "Updated for new features", "Fixed typo").'),
      '#maxlength' => 255,
    ];

    $form['character_count'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['character-count'],
        'id' => 'instructions-character-count',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Instructions'),
      '#button_type' => 'primary',
    ];

    $form['actions']['sync'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync from API'),
      '#submit' => ['::syncFromApi'],
    ];

    $form['actions']['force_sync'] = [
      '#type' => 'submit',
      '#value' => $this->t('Force Sync'),
      '#submit' => ['::forceSyncFromApi'],
      '#button_type' => 'small',
      '#attributes' => [
        'title' => $this->t('Force sync ignoring the 10-second cooldown period'),
      ],
    ];

    // Add JavaScript for character counting.
    $form['#attached']['drupalSettings']['aiEngineChatSystemInstructions'] = [
      'maxLength' => 4000,
      'warningThreshold' => 3500,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $instructions = trim($form_state->getValue('instructions'));
    
    if (empty($instructions)) {
      $form_state->setErrorByName('instructions', $this->t('System instructions cannot be empty.'));
    }
    
    // Soft validation - warn but don't prevent submission for large content.
    if (strlen($instructions) > 4000) {
      $this->messenger()->addWarning($this->t('Instructions are @count characters, which exceeds the recommended maximum of 4,000 characters. This may impact AI performance.', [
        '@count' => strlen($instructions),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $instructions = trim($form_state->getValue('instructions'));
    $notes = trim($form_state->getValue('notes'));

    $result = $this->instructionsManager->saveInstructions($instructions, $notes);

    if ($result['success']) {
      $this->messenger()->addMessage($result['message']);
    }
    else {
      $this->messenger()->addError($result['message']);
    }
  }

  /**
   * Submit handler for sync from API.
   */
  public function syncFromApi(array &$form, FormStateInterface $form_state) {
    $result = $this->instructionsManager->syncFromApi();

    if ($result['success']) {
      $this->messenger()->addMessage($result['message']);
    }
    else {
      $this->messenger()->addError($result['message']);
    }
  }

  /**
   * Submit handler for force sync from API.
   */
  public function forceSyncFromApi(array &$form, FormStateInterface $form_state) {
    $result = $this->instructionsManager->syncFromApi(TRUE);

    if ($result['success']) {
      $this->messenger()->addMessage($result['message']);
    }
    else {
      $this->messenger()->addError($result['message']);
    }
  }

}