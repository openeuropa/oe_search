<?php

declare(strict_types = 1);

namespace Drupal\oe_search_test\Plugin\ServiceMock;

use Drupal\Core\Plugin\PluginBase;
use Drupal\http_request_mock\ServiceMockPluginInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Intercepts any HTTP request made to example.com.
 *
 * @ServiceMock(
 *   id = "europa_search_server_response",
 *   label = @Translation("Europa Search mocked server responses for tesing."),
 *   weight = -1,
 * )
 */
class EuropaSearchServerResponse extends PluginBase implements ServiceMockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request, array $options): bool {
    $this->collectCalledMethods($request->getUri()->getPath(), __FUNCTION__);
    // @todo implement collectRequests().
    return $request->getUri()->getHost() === 'example.com';
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(RequestInterface $request, array $options): ResponseInterface {
    $path = $request->getUri()->getPath();
    $this->collectCalledMethods($path, __FUNCTION__);

    switch ($path) {
      case '/token':
        $response = $this->getTokenResponse();
        break;

      case '/ingest/text':
        $response = $this->getIngestTextResponse();
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
    $calls = $state->get('oe_search_test.service_mock_calls', []);

    if (!isset($calls[$path])) {
      $calls[$path] = [
        'applies' => 0,
        'getResponse' => 0,
      ];
    }

    $calls[$path][$method]++;
    $state->set('oe_search_test.service_mock_calls', $calls);
  }

  /**
   * Get mocked token response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getTokenResponse(): ResponseInterface {
    return new Response(200, [], json_encode([
      'access_token' => 'JWT_TOKEN',
      'scope' => 'APPLICATION_SCOPE',
      'token_type' => 'Bearer',
      'expires_in' => 3600,
    ]));
  }

  /**
   * Get mocked ingest text response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getIngestTextResponse(): ResponseInterface {
    return new Response(200, [], json_encode([
      'apiVersion' => '2.67',
      'reference' => 'foo',
      'trackingId' => 'bar',
    ]));
  }

}
