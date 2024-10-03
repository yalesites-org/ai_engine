<?php

namespace Drupal\ai_engine_embedding\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ai_engine_embedding\Service\EntityUpdate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Setting form for the AI Engine Embedding module.
 */
class AiEngineEmbeddingSettings extends ConfigFormBase {

  /**
   * Config name.
   *
   * @var string
   */
  const CONFIG_NAME = 'ai_engine_embedding.settings';

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ai_engine_embedding_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable embedding services'),
      '#default_value' => $config->get('enable') ?? FALSE,
      '#description' => $this->t('Enable automatic updates of vector database.'),
    ];
    $form['azure_search_service_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure Search Service Name'),
      '#description' => $this->t('Ex: yalehospitalitye2dev'),
      '#default_value' => $config->get('azure_search_service_name') ?? NULL,
    ];
    $form['azure_search_service_index'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure Search Service Index'),
      '#description' => $this->t('Ex: askyalehealth'),
      '#default_value' => $config->get('azure_search_service_index') ?? NULL,
    ];
    $form['azure_embedding_service_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Azure Embedding Service Endpoint'),
      '#description' => $this->t('Ex: https://askyaleindexfunc.azurewebsites.net'),
      '#default_value' => $config->get('azure_embedding_service_url') ?? NULL,
    ];
    $form['azure_chunk_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Chunk Size'),
      '#description' => $this->t('The chunk size to split each document into'),
      '#default_value' => $config->get('azure_chunk_size') ?? 3000,
    ];
    $form['included_media_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Included Media Types'),
      '#options' => $this->getMediaTypes(),
      '#default_value' => array_keys($config->get('included_media_types') ?? []),
      '#default_value' => $config->get('included_media_types') ?? [],
    ];
    $form['actions'] = [
      '#type' => 'details',
      '#title' => $this->t('Embedding Operations'),
      '#open' => FALSE,
    ];
    $form['actions']['upsert_all_documents'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upsert All Documents'),
      '#submit' => ['::actionUpsertAllDocuments'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $enable = $form_state->getValue('enable');
    $name = $form_state->getValue('azure_search_service_name');
    $index = $form_state->getValue('azure_search_service_index');
    $url = $form_state->getValue('azure_embedding_service_url');
    if ($enable && (empty($name) || empty($index) || empty($url))) {
      $form_state->setErrorByName(
        'enable',
        $this->t('You cannot enable embedding services without providing values for Azure Search Service Name, Azure Search Service Index, and Azure Embedding Service Endpoint.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $azure_chunk_size = $form_state->getValue('azure_chunk_size') ?? EntityUpdate::CHUNK_SIZE_DEFAULT;

    if (!is_numeric($azure_chunk_size) || $azure_chunk_size < 1) {
      $azure_chunk_size = EntityUpdate::CHUNK_SIZE_DEFAULT;
    }

    $this->config(self::CONFIG_NAME)
      ->set('enable', $form_state->getValue('enable'))
      ->set('azure_search_service_name', $form_state->getValue('azure_search_service_name'))
      ->set('azure_search_service_index', $form_state->getValue('azure_search_service_index'))
      ->set('azure_embedding_service_url', $form_state->getValue('azure_embedding_service_url'))
      ->set('azure_chunk_size', $azure_chunk_size)
      ->set('included_media_types', $form_state->getValue('included_media_types'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function actionUpsertAllDocuments(array &$form, FormStateInterface $form_state) {
    $service = \Drupal::service('ai_engine_embedding.entity_update');
    $service->addAllDocuments();
  }

  /**
   * Retrieves the list of media types.
   *
   * @return array
   *   An array of media type labels.
   */
  protected function getMediaTypes() {
    $media_types = [];
    foreach ($this->entityTypeManager->getStorage('media_type')->loadMultiple() as $media_type) {
      $media_types[$media_type->id()] = $media_type->label();
    }

    return $media_types;
  }

}
