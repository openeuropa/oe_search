<?php

declare(strict_types = 1);

namespace Drupal\oe_search_mock\Plugin\ServiceMock;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\oe_search_mock\Config\EuropaSearchMockServerConfigOverrider;
use Drupal\http_request_mock\ServiceMockPluginInterface;
use Drupal\oe_search_mock\EuropaSearchMockEvent;
use GuzzleHttp\Psr7\Response;
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->eventDispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher')
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

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_SEARCH:
        $response = $this->getSearchResponse();
        break;

      default:
        $response = new Response(200, [], 'Mocking example.com response');
        break;
    }

    return $response;
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
   * Get mocked search response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getSearchResponse(): ResponseInterface {
    return new Response(200, [], $this->mockedResponses['simple_search_response'] ?? '{}');
  }

}
