<?php

namespace Drupal\ai_engine_feed\Controller;

use Drupal\ai_engine_feed\Service\Sources;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ContentFeed.
 *
 * Returns a JSON feeds of website content for AI ingestion.
 *
 * @package Drupal\ai_engine_feed\Controller
 */
class ContentFeed extends ControllerBase {

  /**
   * The AI Feed Sources service.
   *
   * @var \Drupal\ai_engine_feed\Service\Sources
   */
  protected $sources;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Returns content and metadata in a JSON response.
   *
   * @var \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function jsonResponse() {
    $params = $this->requestStack->getCurrentRequest()->query->all();
    $response = new JsonResponse($this->sources->getContent($params));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   * Constructs a new ContentFeed controller.
   *
   * @param \Drupal\ai_engine_feed\Service\Sources $sources
   *   The AI Feed Sources service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(Sources $sources, RequestStack $request_stack) {
    $this->sources = $sources;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ai_engine_feed.sources'),
      $container->get('request_stack'),
    );
  }

}
