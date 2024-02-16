<?php

namespace Drupal\ai_engine_feed\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ai_engine_feed\Service\Sources;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

    $page = $this->requestStack->getCurrentRequest()->get('page') ?? 1;

    // Tests the query parameter to make sure we have a positive integer.
    $filter_options = [
      'options' => [
        'min_range' => 1,
      ],
    ];

    if (filter_var($page, FILTER_VALIDATE_INT, $filter_options) == FALSE) {
      $page = 1;
    }

    $content = $this->sources->getContent($page);
    $response = new JsonResponse($content);
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
