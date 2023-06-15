<?php

declare(strict_types = 1);

namespace Drupal\oe_search_mock;

use Drupal\oe_search_mock\Config\EuropaSearchMockServerConfigOverrider;
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
    $filters = $sort_parts = $facet_fields_parts = [];
    $path = $request->getUri()->getPath();

    if (!empty($boundary)) {
      $request_parts = $this->getRequestMultipartStreamResources($request, $boundary);
      $request->getBody()->rewind();
      $search_parts = explode("\r\n", $request_parts[0]);
      if ($path === EuropaSearchMockServerConfigOverrider::ENDPOINT_SEARCH) {
        $sort_parts = isset($request_parts[1]) ? explode("\r\n", $request_parts[1]) : [];
      }
      elseif ($path === EuropaSearchMockServerConfigOverrider::ENDPOINT_FACET) {
        $request_position = count($request_parts) - 1;
        $facet_fields_parts = isset($request_parts[$request_position]) ? explode("\r\n", $request_parts[$request_position]) : [];
      }
      $query_parameters = json_decode($search_parts[5], TRUE);
      // Single term conversion.
      if (!empty($query_parameters) && empty($query_parameters['bool']) && !empty($query_parameters['term'])) {
        $query_parameters = ['bool' => ['must' => [$query_parameters]]];
      }

      // Prepare the filters.
      if (!empty($query_parameters['bool']['must'])) {
        $filters = $this->prepareFilters($query_parameters['bool']['must'], $filters);
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

    if ($facet_fields_parts) {
      $facet_fields = json_decode($facet_fields_parts[5], TRUE);
      if (empty($facet_fields['bool'])) {
        $filters['display_fields'] = $facet_fields;
      }
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
   * Prepare filters.
   *
   * @param array $parameters
   *   The parameters.
   * @param array $filters
   *   Filters.
   *
   * @return array
   *   The prepared filters.
   */
  protected function prepareFilters(array $parameters, array $filters = []): array {
    foreach ($parameters as $param) {
      if (!empty($param['bool']['must'])) {
        $filters = $this->prepareFilters($param['bool']['must'], $filters);
      }

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
