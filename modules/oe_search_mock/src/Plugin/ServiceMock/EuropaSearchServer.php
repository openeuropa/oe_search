<?php

declare(strict_types = 1);

namespace Drupal\oe_search_mock\Plugin\ServiceMock;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\oe_search_mock\Config\EuropaSearchMockServerConfigOverrider;
use Drupal\http_request_mock\ServiceMockPluginInterface;
use Drupal\oe_search_mock\EuropaSearchFixturesGenerator;
use Drupal\oe_search_mock\EuropaSearchMockEvent;
use Drupal\oe_search_mock\EuropaSearchMockResponseEvent;
use GuzzleHttp\Psr7\Response;
use OpenEuropa\Tests\EuropaSearchClient\Traits\AssertTestRequestTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Intercepts any HTTP request made to example.com.
 *
 * @ServiceMock(
 *   id = "europa_search_server_response",
 *   label = @Translation("Europa Search mocked server responses for testing."),
 *   weight = -1,
 * )
 */
class EuropaSearchServer extends PluginBase implements ServiceMockPluginInterface, ContainerFactoryPluginInterface {

  use AssertTestRequestTrait;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Mocked responses in JSON format.
   *
   * @var array
   */
  protected $mockedResponses;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Constructs a GotoAction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity bundle service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $dispatcher, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventDispatcher = $dispatcher;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request, array $options): bool {
    $this->collectCalledMethods($request->getUri()->getPath(), __FUNCTION__);
    return $request->getUri()->getHost() === EuropaSearchMockServerConfigOverrider::ENDPOINT_DOMAIN;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(RequestInterface $request, array $options): ResponseInterface {
    $path = $request->getUri()->getPath();
    $this->collectCalledMethods($path, __FUNCTION__);
    $this->collectRequests($path, $request);

    $event = new EuropaSearchMockEvent();
    $this->eventDispatcher->dispatch(EuropaSearchMockEvent::EUROPA_SEARCH_MOCK_EVENT, $event);
    $this->mockedResponses = $event->getResources();

    switch ($path) {
      case EuropaSearchMockServerConfigOverrider::ENDPOINT_INFO:
        $response = $this->getInfoResponse();
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_TOKEN:
        $response = $this->getTokenResponse();
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_TEXT:
        $response = $this->getIngestTextResponse();
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_FILE:
        $response = $this->getIngestTextResponse();
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_DELETE:
        $response = $this->getDeleteResponse();
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_FACET:
        $response = $this->getFacetsResponse();
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_SEARCH:
        $filters = $this->getFiltersFromRequest($request);
        $json = EuropaSearchFixturesGenerator::getSearchJson($filters);
        $response = new Response(200, [], $json);
        break;

      default:
        $response = new Response(200, [], 'Mocking example.com response');
        break;
    }

    $event = new EuropaSearchMockResponseEvent($request, $response);
    $this->eventDispatcher->dispatch(EuropaSearchMockResponseEvent::EUROPA_SEARCH_MOCK_RESPONSE_EVENT, $event);
    return $event->getResponse();
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
    if (!$boundary) {
      return [];
    }
    $request_parts = $this->getRequestMultipartStreamResources($request, $boundary);
    $request->getBody()->rewind();
    $search_parts = explode("\r\n", $request_parts[0]);
    $sort_parts = isset($request_parts[1]) ? explode("\r\n", $request_parts[1]) : [];
    $query_parameters = json_decode($search_parts[5], TRUE);
    if (!$query_parameters || !isset($query_parameters['bool']['must'])) {
      return [];
    }

    // Prepare the filters.
    $filters = [];
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
    parse_str($request->getUri()->getQuery(), $url_query_parameters);
    if (isset($url_query_parameters['text'])) {
      $filters['TEXT'] = $url_query_parameters['text'];
    }
    if (isset($url_query_parameters['pageNumber'])) {
      $filters['PAGE'] = $url_query_parameters['pageNumber'];
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

  /**
   * Counts how many times each method of this class were called.
   *
   * @param string $path
   *   The request path.
   * @param string $method
   *   The method being called.
   */
  protected function collectCalledMethods(string $path, string $method): void {
    $state = \Drupal::state();
    $calls = $state->get('oe_search_mock.service_mock_calls', []);

    if (!isset($calls[$path])) {
      $calls[$path] = [
        'applies' => 0,
        'getResponse' => 0,
      ];
    }

    $calls[$path][$method]++;
    $state->set('oe_search_mock.service_mock_calls', $calls);
  }

  /**
   * Collects the requests received.
   *
   * @param string $path
   *   The request path.
   * @param \Psr\Http\Message\RequestInterface $request
   *   The received request.
   */
  protected function collectRequests(string $path, RequestInterface $request): void {
    $state = \Drupal::state();
    $requests = $state->get('oe_search_mock.service_mock_requests', []);
    $requests[$path][] = $request;
    $state->set('oe_search_mock.service_mock_requests', $requests);
  }

  /**
   * Get mocked token response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getInfoResponse(): ResponseInterface {
    return new Response(200, [], $this->mockedResponses['info_response'] ?? '{}');
  }

  /**
   * Get mocked token response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getTokenResponse(): ResponseInterface {
    return new Response(200, [], $this->mockedResponses['jwt_response'] ?? '{}');
  }

  /**
   * Get mocked ingest text response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getIngestTextResponse(): ResponseInterface {
    return new Response(200, [], $this->mockedResponses['text_ingestion_response'] ?? '{}');
  }

  /**
   * Get mocked delete response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getDeleteResponse(): ResponseInterface {
    return new Response(200, [], $this->mockedResponses['delete_document_response'] ?? '{}');
  }

  /**
   * Get mocked facets response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getFacetsResponse(): ResponseInterface {
    return new Response(200, [], $this->mockedResponses['facets_response'] ?? '{}');
  }

}
