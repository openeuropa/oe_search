<?php

declare(strict_types=1);

namespace Drupal\oe_search_mock;

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Psr7\Request;

/**
 * Helper class to collect the request stack in a serializable way.
 */
class EuropaSearchMockRequestCollector {

  /**
   * Collects the request and stores it in a serializable form into the state.
   *
   * @param string $path
   *   The request path.
   * @param \Psr\Http\Message\RequestInterface $request
   *   The received request.
   */
  public static function collectRequests(string $path, RequestInterface $request): void {
    $state = \Drupal::state();
    $requests = $state->get('oe_search_mock.service_mock_requests', []);
    $requests[$path][] = static::serializeToArray($request);
    $state->set('oe_search_mock.service_mock_requests', $requests);
  }

  /**
   * Gets the requests that were collected before from the state.
   *
   * @param string $path
   *   The request path.
   *
   * @return \Psr\Http\Message\RequestInterface[]
   *   The array of request objects.
   */
  public static function getCollectedRequests(string $path): array {
    $state = \Drupal::state();
    $requests = $state->get('oe_search_mock.service_mock_requests', []);
    $request_objects = [];

    if (empty($requests[$path])) {
      return [];
    }

    foreach ($requests[$path] as $request) {
      $request_objects[] = static::deserializeFromArray($request);
    }
    return $request_objects;
  }

  /**
   * Pops a collected request from the stack.
   *
   * @param string $path
   *   The request path.
   *
   * @return \Psr\Http\Message\RequestInterface|null
   *   The request object.
   */
  public static function popCollectedRequest(string $path): ?RequestInterface {
    $state = \Drupal::state();
    $requests = $state->get('oe_search_mock.service_mock_requests', []);

    if (empty($requests[$path])) {
      return NULL;
    }

    $request_object = static::deserializeFromArray(array_pop($requests[$path]));
    $state->set('oe_search_mock.service_mock_requests', $requests);

    return $request_object;
  }

  /**
   * Checks if there are or were any requests for the given path.
   *
   * @param string $path
   *   The request path.
   *
   * @return bool
   *   TRUE if there are or were any requests for the given path.
   */
  public static function hasCollectedRequests(string $path): bool {
    $state = \Drupal::state();
    $requests = $state->get('oe_search_mock.service_mock_requests', []);

    return isset($requests[$path]);
  }

  /**
   * Serializes a request to an array.
   *
   * This type of serialization is needed when we need to use php serializer
   * other than default. In this case, streams are not serializable, so we
   * have to store their content in a text form.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The Request object.
   *
   * @return array
   *   The array representation of the objects.
   */
  public static function serializeToArray(RequestInterface $request): array {
    return [
      'method' => $request->getMethod(),
      'uri' => (string) $request->getUri(),
      'headers' => $request->getHeaders(),
      'body' => (string) $request->getBody(),
      'protocol_version' => $request->getProtocolVersion(),
    ];
  }

  /**
   * Convert an array representation back to a request object.
   *
   * @param array $data
   *   The serialized request array.
   *
   * @return \Psr\Http\Message\RequestInterface
   *   The reconstructed request object.
   */
  public static function deserializeFromArray(array $data): RequestInterface {
    return new Request(
      $data['method'] ?? 'GET',
      $data['uri'] ?? '',
      $data['headers'] ?? [],
      $data['body'] ?? NULL,
      $data['protocol_version'] ?? '1.1'
    );
  }

}
