<?php

namespace Drupal\Tests\ai_engine_feed\Unit\Controller;

use Drupal\ai_engine_feed\Controller\ContentFeed;
use Drupal\ai_engine_feed\Service\Sources;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ai_engine_feed\Controller\ContentFeed
 *
 * @group ai_engine_feed
 */
class ContentFeedTest extends UnitTestCase {

  /**
   * The AI Feed Sources service mock.
   *
   * @var \Drupal\ai_engine_feed\Service\Sources|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sourcesMock;

  /**
   * The ContentFeed controller.
   *
   * @var \Drupal\ai_engine_feed\Controller\ContentFeed
   */
  protected $contentFeed;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // Create a mock for the AI Feed Sources service.
    $this->sourcesMock = $this->createMock(Sources::class);
    // Create an instance of the ContentFeed controller with the mock service.
    $this->contentFeed = new ContentFeed($this->sourcesMock);
  }

  /**
   * Tests the jsonResponse method.
   */
  public function testJsonResponse() {
    $sampleContent = [
      'title' => 'Sample Title',
      'body' => 'Sample Body',
      'author' => 'Sample Author',
    ];

    // Expect the getContent method to be called once.
    // Return sample data to focus this test on only the controller.
    $this->sourcesMock
      ->expects($this->once())
      ->method('getContent')
      ->willReturn($sampleContent);

    // Call the jsonResponse method.
    $response = $this->contentFeed->jsonResponse();

    // Check that the returned response is an instance of JsonResponse.
    $this->assertInstanceOf(JsonResponse::class, $response);

    // Check that the response content is the expected sample content.
    $this->assertEquals(json_encode($sampleContent), $response->getContent());

    // Check that the Content-Type header is set to 'application/json'.
    $this->assertEquals('application/json', $response->headers->get('Content-Type'));
  }

}
