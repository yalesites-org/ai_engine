<?php

namespace Drupal\ai_engine_system_instructions\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service for orchestrating system instructions management.
 */
class SystemInstructionsManagerService {

  use StringTranslationTrait;

  /**
   * The API service.
   *
   * @var \Drupal\ai_engine_system_instructions\Service\SystemInstructionsApiService
   */
  protected $apiService;

  /**
   * The storage service.
   *
   * @var \Drupal\ai_engine_system_instructions\Service\SystemInstructionsStorageService
   */
  protected $storageService;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The key-value store for caching.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The text format detection service.
   *
   * @var \Drupal\ai_engine_system_instructions\Service\TextFormatDetectionService
   */
  protected $textFormatDetection;

  /**
   * API sync cooldown period in seconds.
   */
  const API_SYNC_COOLDOWN = 10;

  /**
   * Constructs a SystemInstructionsManagerService.
   *
   * @param \Drupal\ai_engine_system_instructions\Service\SystemInstructionsApiService $api_service
   *   The API service.
   * @param \Drupal\ai_engine_system_instructions\Service\SystemInstructionsStorageService $storage_service
   *   The storage service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value store factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\ai_engine_system_instructions\Service\TextFormatDetectionService $text_format_detection
   *   The text format detection service.
   */
  public function __construct(SystemInstructionsApiService $api_service, SystemInstructionsStorageService $storage_service, LoggerChannelFactoryInterface $logger_factory, KeyValueFactoryInterface $key_value_factory, TimeInterface $time, TextFormatDetectionService $text_format_detection) {
    $this->apiService = $api_service;
    $this->storageService = $storage_service;
    $this->logger = $logger_factory->get('ai_engine_system_instructions');
    $this->keyValueStore = $key_value_factory->get('ai_engine_system_instructions');
    $this->time = $time;
    $this->textFormatDetection = $text_format_detection;
  }

  /**
   * Sync system instructions from API.
   *
   * This fetches the latest instructions from the API and creates a new
   * version if they differ from the current active version. Includes a
   * cooldown period to prevent excessive API calls.
   *
   * @param bool $force
   *   Whether to force sync ignoring the cooldown period.
   *
   * @return array
   *   Array with 'success' (bool), 'message' (string), 'version' (int).
   */
  public function syncFromApi(bool $force = FALSE): array {
    // Check cooldown period unless forced.
    if (!$force) {
      $last_sync_time = $this->keyValueStore->get('last_api_sync_time', 0);
      $current_time = $this->time->getRequestTime();
      $time_since_last_sync = $current_time - $last_sync_time;

      if ($time_since_last_sync < self::API_SYNC_COOLDOWN) {
        $remaining_cooldown = self::API_SYNC_COOLDOWN - $time_since_last_sync;
        return [
          'success' => TRUE,
          'local_success' => TRUE,
          'api_success' => TRUE,
          'skipped' => TRUE,
          'message' => $this->t('API sync skipped. Please wait @seconds more seconds before syncing again.', [
            '@seconds' => $remaining_cooldown,
          ]),
          'version' => $this->storageService->getActiveInstructions()['version'] ?? NULL,
        ];
      }
    }

    // Record the sync attempt time.
    $this->keyValueStore->set('last_api_sync_time', $this->time->getRequestTime());

    $api_result = $this->apiService->getSystemInstructions();

    if (!$api_result['success']) {
      // Log the error but don't fail the entire operation.
      $this->logger->warning('API sync failed: @error', ['@error' => $api_result['error']]);

      return [
        'success' => FALSE,
        'local_success' => TRUE,
        'api_success' => FALSE,
        'message' => 'Could not sync with API: ' . $api_result['error'] . ' (using local version)',
        'api_error' => $api_result['error'],
        'version' => $this->storageService->getActiveInstructions()['version'] ?? NULL,
      ];
    }

    $api_instructions = $api_result['data'];

    // Check if these instructions are different from current active version.
    if (!$this->storageService->areInstructionsDifferent($api_instructions)) {
      return [
        'success' => TRUE,
        'local_success' => TRUE,
        'api_success' => TRUE,
        'message' => 'Instructions are already up to date.',
        'version' => $this->storageService->getActiveInstructions()['version'] ?? NULL,
      ];
    }

    // Format and create new version with system user (ID 1 for API sync).
    $formatted_instructions = $this->textFormatDetection->formatText($api_instructions);
    $new_version = $this->storageService->createVersion(
      $formatted_instructions,
      'Synced from API',
      1
    );

    $this->logger->info('System instructions synced from API. New version: @version', [
      '@version' => $new_version,
    ]);

    return [
      'success' => TRUE,
      'local_success' => TRUE,
      'api_success' => TRUE,
      'message' => 'Instructions synced successfully. New version: ' . $new_version,
      'version' => $new_version,
    ];
  }

  /**
   * Save system instructions both locally and to API.
   *
   * @param string $instructions
   *   The instructions to save.
   * @param string $notes
   *   Optional notes about this version.
   *
   * @return array
   *   Array with 'success' (bool), 'message' (string), 'version' (int).
   */
  public function saveInstructions(string $instructions, string $notes = ''): array {
    // First, check if instructions are different from current version.
    if (!$this->storageService->areInstructionsDifferent($instructions)) {
      return [
        'success' => TRUE,
        'local_success' => TRUE,
        'api_success' => TRUE,
        'message' => 'No changes detected. Instructions not saved.',
        'version' => $this->storageService->getActiveInstructions()['version'] ?? NULL,
      ];
    }

    // Create local version first.
    $new_version = $this->storageService->createVersion($instructions, $notes);

    // Try to push to API.
    $api_result = $this->apiService->setSystemInstructions($instructions);

    if (!$api_result['success']) {
      $this->logger->error('Failed to save system instructions to API: @error', [
        '@error' => $api_result['error'],
      ]);

      return [
        'success' => FALSE,
        'local_success' => TRUE,
        'api_success' => FALSE,
        'message' => 'Local version saved but API update failed: ' . $api_result['error'],
        'api_error' => $api_result['error'],
        'version' => $new_version,
      ];
    }

    $this->logger->info('System instructions saved successfully. Version: @version', [
      '@version' => $new_version,
    ]);

    return [
      'success' => TRUE,
      'local_success' => TRUE,
      'api_success' => TRUE,
      'message' => 'Instructions saved successfully. Version: ' . $new_version,
      'version' => $new_version,
    ];
  }

  /**
   * Get current system instructions with API sync check.
   *
   * This method first tries to sync from the API, then returns the active
   * instructions. If API sync fails, it returns the local active version.
   *
   * @return array
   *   Array with 'instructions', 'version', 'synced', 'sync_error' keys.
   */
  public function getCurrentInstructions(): array {
    $sync_result = $this->syncFromApi();

    $active = $this->storageService->getActiveInstructions();

    if (!$active) {
      return [
        'instructions' => '',
        'version' => 0,
        'synced' => $sync_result['success'],
        'sync_error' => $sync_result['success'] ? '' : $sync_result['message'],
      ];
    }

    return [
      'instructions' => $this->textFormatDetection->formatText($active['instructions']),
      'version' => (int) $active['version'],
      'synced' => $sync_result['success'],
      'sync_error' => $sync_result['success'] ? '' : $sync_result['message'],
    ];
  }

  /**
   * Revert to a previous version and sync to API.
   *
   * @param int $version
   *   The version number to revert to.
   *
   * @return array
   *   Array with 'success' (bool) and 'message' (string).
   */
  public function revertToVersion(int $version): array {
    $target_version = $this->storageService->getVersion($version);

    if (!$target_version) {
      return [
        'success' => FALSE,
        'message' => 'Version ' . $version . ' not found.',
      ];
    }

    // Set as active version.
    $this->storageService->setActiveVersion($version);

    // Push to API.
    $api_result = $this->apiService->setSystemInstructions($target_version['instructions']);

    if (!$api_result['success']) {
      $this->logger->error('Failed to revert system instructions in API: @error', [
        '@error' => $api_result['error'],
      ]);

      return [
        'success' => FALSE,
        'message' => 'Local version reverted but API update failed: ' . $api_result['error'],
      ];
    }

    $this->logger->info('System instructions reverted to version: @version', [
      '@version' => $version,
    ]);

    return [
      'success' => TRUE,
      'message' => 'Successfully reverted to version ' . $version,
    ];
  }

  /**
   * Get all versions for display.
   *
   * @return array
   *   Array of version data for admin display.
   */
  public function getAllVersions(): array {
    return $this->storageService->getAllVersions();
  }

  /**
   * Get version statistics.
   *
   * @return array
   *   Array with version count and active version info.
   */
  public function getVersionStats(): array {
    $active = $this->storageService->getActiveInstructions();

    return [
      'total_versions' => $this->storageService->getVersionCount(),
      'active_version' => $active ? (int) $active['version'] : 0,
      'active_created' => $active ? (int) $active['created_date'] : 0,
    ];
  }


  /**
   * Get the storage service.
   *
   * @return \Drupal\ai_engine_system_instructions\Service\SystemInstructionsStorageService
   *   The storage service.
   */
  public function getStorageService(): SystemInstructionsStorageService {
    return $this->storageService;
  }

}