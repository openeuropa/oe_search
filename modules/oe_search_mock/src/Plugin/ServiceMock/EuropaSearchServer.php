<?php

declare(strict_types=1);

namespace Drupal\oe_search_mock\Plugin\ServiceMock;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\http_request_mock\ServiceMockPluginInterface;
use Drupal\oe_search_mock\Config\EuropaSearchMockServerConfigOverrider;
use Drupal\oe_search_mock\EuropaSearchMockEvent;
use Drupal\oe_search_mock\EuropaSearchMockRequestCollector;
use Drupal\oe_search_mock\EuropaSearchMockResponseEvent;
use Drupal\oe_search_mock\EuropaSearchMockTrait;
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

  use EuropaSearchMockTrait;

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
  protected $entityTypeBundleInfo;

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
    EuropaSearchMockRequestCollector::collectRequests($path, $request);

    parse_str($request->getUri()->getQuery(), $parameters);
    $event = new EuropaSearchMockEvent();
    $this->eventDispatcher->dispatch($event, EuropaSearchMockEvent::EUROPA_SEARCH_MOCK_EVENT);
    $this->mockedResponses = $event->getResources();

    switch ($path) {
      case EuropaSearchMockServerConfigOverrider::ENDPOINT_INFO:
        $response = $this->getInfoResponse();
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_TOKEN:
        $response = $this->getTokenResponse();
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_TEXT:
        $response = $this->getIngestTextResponse($parameters['reference']);
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_FILE:
        $response = $this->getIngestTextResponse($parameters['reference']);
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_TRACKING_STATUS:
        $response = $this->getIngestionTrackingResponse();
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_DELETE:
        $params = urldecode($request->getUri()->getQuery());
        // Make it fail on the 5th entity.
        if (str_contains($params, 'entity_test_mulrev_changed/5')) {
          $response = $this->getFailedDeleteResponse();
        }
        else {
          $response = $this->getDeleteResponse();
        }

        break;

      default:
        $response = new Response(200, [], 'Mocking example.com response');
        break;
    }

    $event = new EuropaSearchMockResponseEvent($request, $response);
    $this->eventDispatcher->dispatch($event, EuropaSearchMockResponseEvent::EUROPA_SEARCH_MOCK_RESPONSE_EVENT);
    return $event->getResponse();
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
   * @param string $reference
   *   The reference.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getIngestTextResponse(string $reference): ResponseInterface {
    $response = $this->mockedResponses['text_ingestion_response'];

    if (empty($response)) {
      return new Response(200, [], '{}');
    }
    $json = json_decode($response);
    if (empty($json)) {
      return new Response(200, [], '{}');
    }

    $json->reference = $reference;
    $json->trackingId = \Drupal::service('uuid')->generate();
    return new Response(200, [], json_encode($json) ?? '{}');
  }

  /**
   * Get mocked ingestion tracking status response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getIngestionTrackingResponse(): ResponseInterface {
    return new Response(200, [], $this->mockedResponses['ingestion_tracking_response'] ?? '[]');
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
   * Get mocked failed delete response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getFailedDeleteResponse(): ResponseInterface {
    return new Response(500, [], $this->mockedResponses['delete_document_response'] ?? '{}');
  }

}
