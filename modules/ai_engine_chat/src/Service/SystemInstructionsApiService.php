<?php

namespace Drupal\ai_engine_chat\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for managing system instructions API calls.
 */
class SystemInstructionsApiService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * API timeout in seconds.
   */
  const API_TIMEOUT = 30;

  /**
   * Constructs a SystemInstructionsApiService.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, KeyRepositoryInterface $key_repository, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->keyRepository = $key_repository;
    $this->logger = $logger_factory->get('ai_engine_chat');
  }

  /**
   * Get system instructions from the API.
   *
   * @return array
   *   Array with 'success' (bool), 'data' (string), and 'error' (string) keys.
   */
  public function getSystemInstructions(): array {
    $config = $this->getApiConfig();
    if (!$config) {
      return [
        'success' => FALSE,
        'data' => '',
        'error' => 'API configuration is incomplete.',
      ];
    }

    $payload = [
      'action' => 'get',
      'web_app_name' => $config['web_app_name'],
      'environment_variables' => ['AZURE_OPENAI_SYSTEM_MESSAGE'],
    ];

    try {
      $response = $this->httpClient->request('POST', $config['api_endpoint'], [
        'json' => $payload,
        'headers' => [
          'x-functions-key' => $config['api_key'],
          'Content-Type' => 'application/json',
        ],
        'timeout' => self::API_TIMEOUT,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if ($response->getStatusCode() === 200 && isset($data['AZURE_OPENAI_SYSTEM_MESSAGE'])) {
        return [
          'success' => TRUE,
          'data' => $data['AZURE_OPENAI_SYSTEM_MESSAGE'],
          'error' => '',
        ];
      }

      return [
        'success' => FALSE,
        'data' => '',
        'error' => 'Invalid API response format.',
      ];

    }
    catch (RequestException $e) {
      $this->logger->error('Failed to get system instructions from API: @error', [
        '@error' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'data' => '',
        'error' => 'API request failed: ' . $e->getMessage(),
      ];
    }
  }

  /**
   * Set system instructions via the API.
   *
   * @param string $instructions
   *   The system instructions to set.
   *
   * @return array
   *   Array with 'success' (bool) and 'error' (string) keys.
   */
  public function setSystemInstructions(string $instructions): array {
    $config = $this->getApiConfig();
    if (!$config) {
      return [
        'success' => FALSE,
        'error' => 'API configuration is incomplete.',
      ];
    }

    $payload = [
      'action' => 'set',
      'web_app_name' => $config['web_app_name'],
      'environment_variables' => [
        'AZURE_OPENAI_SYSTEM_MESSAGE' => $instructions,
      ],
    ];

    try {
      $response = $this->httpClient->request('POST', $config['api_endpoint'], [
        'json' => $payload,
        'headers' => [
          'x-functions-key' => $config['api_key'],
          'Content-Type' => 'application/json',
        ],
        'timeout' => self::API_TIMEOUT,
      ]);

      if ($response->getStatusCode() === 200) {
        return [
          'success' => TRUE,
          'error' => '',
        ];
      }

      return [
        'success' => FALSE,
        'error' => 'API returned status code: ' . $response->getStatusCode(),
      ];

    }
    catch (RequestException $e) {
      $this->logger->error('Failed to set system instructions via API: @error', [
        '@error' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'error' => 'API request failed: ' . $e->getMessage(),
      ];
    }
  }

  /**
   * Get API configuration.
   *
   * @return array|null
   *   Configuration array or NULL if incomplete or disabled.
   */
  protected function getApiConfig(): ?array {
    $config = $this->configFactory->get('ai_engine_chat.settings');

    // Check if the feature is enabled.
    if (!$config->get('system_instructions_enabled')) {
      return NULL;
    }

    $api_endpoint = $config->get('system_instructions_api_endpoint');
    $web_app_name = $config->get('system_instructions_web_app_name');
    $api_key_name = $config->get('system_instructions_api_key');

    if (!$api_endpoint || !$web_app_name || !$api_key_name) {
      return NULL;
    }

    $api_key = $this->keyRepository->getKey($api_key_name)?->getKeyValue();
    if (!$api_key) {
      return NULL;
    }

    return [
      'api_endpoint' => $api_endpoint,
      'web_app_name' => $web_app_name,
      'api_key' => $api_key,
    ];
  }

}
