<?php

namespace Drupal\ai_engine_chat\Form;

use Drupal\ai_engine_chat\Service\SystemInstructionsManagerService;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
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

    $form['#attached']['library'][] = 'ai_engine_chat/system_instructions';

    // Create a wrapper for AJAX updates.
    $form['form_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'system-instructions-form-wrapper'],
    ];

    // Check if we need to show loading state.
    // Show loading only on initial page load, not on AJAX rebuilds.
    $show_loading = $form_state->get('show_loading');
    $refreshed = $form_state->get('refreshed');
    
    if ($show_loading === NULL && !$refreshed) {
      // First time loading the form - show loading state.
      $show_loading = TRUE;
      $form_state->set('show_loading', TRUE);
    } else {
      // Already refreshed or explicitly set to FALSE.
      $show_loading = FALSE;
    }

    if ($show_loading) {
      $form['form_wrapper']['loading'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['system-instructions-loading']],
      ];

      $form['form_wrapper']['loading']['spinner'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['ajax-progress', 'ajax-progress-throbber']],
        '#value' => '<div class="throbber">&nbsp;</div>',
      ];

      $form['form_wrapper']['loading']['message'] = [
        '#markup' => '<div class="message">' . $this->t('Syncing with API, please wait...') . '</div>',
      ];

      // Add auto-refresh after a short delay.
      $form['form_wrapper']['refresh'] = [
        '#type' => 'submit',
        '#value' => $this->t('Loading...'),
        '#ajax' => [
          'callback' => '::ajaxRefreshForm',
          'wrapper' => 'system-instructions-form-wrapper',
          'progress' => [
            'type' => 'none',
          ],
        ],
        '#attributes' => [
          'style' => 'display: none;',
          'id' => 'system-instructions-refresh-btn',
        ],
        '#submit' => ['::ajaxRefreshSubmit'],
        '#limit_validation_errors' => [],
      ];

      // Use JavaScript to auto-trigger the refresh.
      $form['#attached']['drupalSettings']['aiEngineSystemInstructions']['autoRefresh'] = TRUE;

      return $form;
    }

    // Get current instructions and sync status.
    try {
      $current = $this->instructionsManager->getCurrentInstructions();
      $stats = $this->instructionsManager->getVersionStats();
    }
    catch (\Exception $e) {
      // If API sync fails, get local version and show error.
      $this->messenger()->addError($this->t('Failed to sync with API: @error', ['@error' => $e->getMessage()]));
      $active = $this->instructionsManager->getStorageService()->getActiveInstructions();
      $current = [
        'instructions' => $active ? $active['instructions'] : '',
        'version' => $active ? (int) $active['version'] : 0,
        'synced' => FALSE,
        'sync_error' => $e->getMessage(),
      ];
      $stats = $this->instructionsManager->getVersionStats();
    }

    // Display sync status and version info.
    $form['form_wrapper']['status'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['system-instructions-status']],
    ];

    if (!$current['synced']) {
      $form['form_wrapper']['status']['sync_warning'] = [
        '#type' => 'item',
        '#markup' => $this->t('<div class="messages messages--warning">Warning: Could not sync with API: @error</div>', [
          '@error' => $current['sync_error'],
        ]),
      ];
    }

    $form['form_wrapper']['status']['info'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p><strong>Current version:</strong> @version | <strong>Total versions:</strong> @total | <a href="@history_url">View version history</a></p>', [
        '@version' => $current['version'] ?: $this->t('None'),
        '@total' => $stats['total_versions'],
        '@history_url' => Url::fromRoute('ai_engine_chat.system_instructions_versions')->toString(),
      ]),
    ];

    $form['form_wrapper']['instructions'] = [
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

    $form['form_wrapper']['notes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Version Notes'),
      '#description' => $this->t('Optional notes about this version (e.g., "Updated for new features", "Fixed typo").'),
      '#maxlength' => 255,
    ];

    $form['form_wrapper']['character_count'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['character-count'],
        'id' => 'instructions-character-count',
      ],
    ];

    $form['form_wrapper']['actions'] = [
      '#type' => 'actions',
    ];

    $form['form_wrapper']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Instructions'),
      '#button_type' => 'primary',
    ];

    $form['form_wrapper']['actions']['sync'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync from API'),
      '#submit' => ['::syncFromApi'],
    ];

    $form['form_wrapper']['actions']['force_sync'] = [
      '#type' => 'submit',
      '#value' => $this->t('Force Sync'),
      '#submit' => ['::forceSyncFromApi'],
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
    // Skip validation during AJAX refresh.
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && isset($triggering_element['#id']) && $triggering_element['#id'] === 'system-instructions-refresh-btn') {
      return;
    }
    
    // Skip validation if we're still in loading state.
    if ($form_state->get('show_loading')) {
      return;
    }

    $instructions = $form_state->getValue('instructions');
    $instructions = $instructions ? trim($instructions) : '';

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

  /**
   * Submit handler for the AJAX refresh.
   */
  public function ajaxRefreshSubmit(array &$form, FormStateInterface $form_state) {
    // Clear the loading state and rebuild the form.
    $form_state->set('show_loading', FALSE);
    $form_state->set('refreshed', TRUE);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback to refresh the form after loading.
   */
  public function ajaxRefreshForm(array &$form, FormStateInterface $form_state) {
    // Return the updated wrapper.
    return $form['form_wrapper'];
  }

}
