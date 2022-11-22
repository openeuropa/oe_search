<?php

declare(strict_types = 1);

namespace Drupal\oe_search_mock;

use Psr\Http\Message\RequestInterface;

/**
 * Specific europa search mock methods.
 */
trait EuropaSearchMockTrait {

  /**
   * Returns the request boundary.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   *
   * @return string
   *   The boundary.
   */
  protected function getRequestBoundary(RequestInterface $request): ?string {
    preg_match('/; boundary="([^"].*)"/', $request->getHeaderLine('Content-Type'), $found);
    return $found[1] ?? NULL;
  }

  /**
   * Gets multipart stream resources if present.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   * @param string $boundary
   *   The boundary.
   *
   * @return false|string[]
   *   Multipart stream resources if present.
   */
  protected function getRequestMultipartStreamResources(RequestInterface $request, string $boundary) {
    $parts = explode("--{$boundary}", $request->getBody()->getContents());
    // The first and last entries are empty.
    // @todo Improve this.
    array_shift($parts);
    array_pop($parts);

    return $parts;
  }

  /**
   * Returns the request filters.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   *
   * @return array
   *   The filters.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function getFiltersFromRequest(RequestInterface $request): array {
    $request->getBody()->rewind();
    $boundary = $this->getRequestBoundary($request);
    $filters = $sort_parts = [];
    if (!empty($boundary)) {
      $request_parts = $this->getRequestMultipartStreamResources($request, $boundary);
      $request->getBody()->rewind();
      $search_parts = explode("\r\n", $request_parts[0]);
      $sort_parts = isset($request_parts[1]) ? explode("\r\n", $request_parts[1]) : [];
      $query_parameters = json_decode($search_parts[5], TRUE);
      // Single term conversion.
      if (!empty($query_parameters) && empty($query_parameters['bool']) && !empty($query_parameters['term'])) {
        $query_parameters = ['bool' => ['must' => [$query_parameters]]];
      }

      if (!$query_parameters || !isset($query_parameters['bool']['must'])) {
        return [];
      }

      // Prepare the filters.
      foreach ($query_parameters['bool']['must'] as $key => $param) {
        if (isset($param['term'])) {
          $filters[key($param['term'])] = reset($param['term']);
        }
        if (isset($param['terms'])) {
          $filters += $param['terms'];
        }
        if (isset($param['range'])) {
          $filters[key($param['range'])] = reset($param['range']);
        }
      }
    }

    parse_str($request->getUri()->getQuery(), $url_query_parameters);
    if (isset($url_query_parameters['text'])) {
      $filters['TEXT'] = $url_query_parameters['text'];
    }
    if (isset($url_query_parameters['pageNumber'])) {
      $filters['PAGE'] = $url_query_parameters['pageNumber'];
    }
    if (isset($url_query_parameters['pageSize']) && $url_query_parameters['pageSize'] !== "10") {
      $filters['page_size'] = $url_query_parameters['pageSize'];
    }

    if ($sort_parts) {
      $sorts = json_decode($sort_parts[5], TRUE);
      $filters['sort'] = $sorts;
    }

    $unset = [
      'SEARCH_API_SITE_HASH',
      'SEARCH_API_INDEX_ID',
    ];

    foreach ($unset as $field) {
      if (isset($filters[$field])) {
        unset($filters[$field]);
      }
    }

    asort($filters);

    return $filters;
  }

  /**
   * Returns the basic info for the mock given the filters.
   *
   * Returns a generated ID based on the filters and the entity type and bundle
   * of the request.
   *
   * @param array $filters
   *   The filters.
   *
   * @return array
   *   The info.
   */
  public static function getMockInfoFromFilters(array $filters): array {
    $scenario_id = md5(serialize($filters));

    $entity_type = explode(':', $filters['SEARCH_API_DATASOURCE'] ?? 'entity:node');
    $entity_type = $entity_type[1];
    $bundle = $filters['TYPE'] ?? NULL;

    return [
      'id' => $scenario_id,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ];
  }

}