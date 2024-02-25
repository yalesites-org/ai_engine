<?php

namespace Drupal\ai_engine_feed;

use Drupal\Core\Url;

/**
 * Trait providing methods to build API links for paginated content.
 */
trait ApiLinkBuilderTrait {

  /**
   * Get the page number from request parameters.
   *
   * @param array $params
   *   An array of URL parameters for the current request.
   *
   * @return int
   *   The current page number for paginated results.
   */
  protected function getPageNumber(array $params): int {
    return $params['page'] ?? 1;
  }

  /**
   * Get the first page link for the API.
   *
   * @param array $params
   *   An array of URL parameters for the current request.
   * @param int $totalPages
   *   Total number of pages.
   *
   * @return string
   *   The API link for the first page.
   */
  protected function getApiLinkFirst(array $params, int $totalPages): string {
    if ($totalPages == 1) {
      unset($params['page']);
    }
    else {
      $params['page'] = 1;
    }
    return $this->getContentEndpoint($params);
  }

  /**
   * Get the previous page link for the API.
   *
   * @param array $params
   *   An array of URL parameters for the current request.
   * @param int $totalPages
   *   Total number of pages.
   *
   * @return string
   *   The API link for the previous page.
   */
  protected function getApiLinkPrevious(array $params, int $totalPages): string {
    $currentPage = $this->getPageNumber($params);
    if ($totalPages == 1 || $currentPage == 1) {
      return "";
    }
    elseif ($totalPages > 1 && $currentPage > 1) {
      $params['page']--;
    }
    return $this->getContentEndpoint($params);
  }

  /**
   * Get the next page link for the API.
   *
   * @param array $params
   *   An array of URL parameters for the current request.
   * @param int $totalPages
   *   Total number of pages.
   *
   * @return string
   *   The API link for the next page.
   */
  protected function getApiLinkNext(array $params, int $totalPages): string {
    $currentPage = $this->getPageNumber($params);
    if ($totalPages == 1 || $currentPage == $totalPages) {
      return "";
    }
    elseif ($totalPages > 1 && $currentPage < $totalPages) {
      $params['page']++;
    }
    return $this->getContentEndpoint($params);
  }

  /**
   * Get the self page link for the API.
   *
   * @param array $params
   *   An array of URL parameters for the current request.
   * @param int $totalPages
   *   Total number of pages.
   *
   * @return string
   *   The API link for the current page.
   */
  protected function getApiLinkSelf(array $params, int $totalPages): string {
    if ($totalPages == 1) {
      unset($params['page']);
    }
    return $this->getContentEndpoint($params);
  }

  /**
   * Get the last page link for the API.
   *
   * @param array $params
   *   An array of URL parameters for the current request.
   * @param int $totalPages
   *   Total number of pages.
   *
   * @return string
   *   The API link for the last page.
   */
  protected function getApiLinkLast(array $params, int $totalPages): string {
    if ($totalPages == 1) {
      unset($params['page']);
    }
    else {
      $params['page'] = $totalPages;
    }
    return $this->getContentEndpoint($params);
  }

  /**
   * Get the URL for the AI content endpoint.
   *
   * @param array $params
   *   An array of URL parameters for the current request.
   *
   * @return string
   *   The fully qualified path to the AI content endpoint.
   */
  public function getContentEndpoint(array $params = []): string {
    $url = Url::fromRoute('ai_engine_feed.content', array_filter($params));
    $url->setAbsolute();
    return $url->toString();
  }

}
