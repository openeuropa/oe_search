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
    $this->collectCalledMethods(__FUNCTION__);
    return $request->getUri()->getHost() === 'example.com';
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(RequestInterface $request, array $options): ResponseInterface {
    $this->collectCalledMethods(__FUNCTION__);
    return new Response(200, [], 'Mocking example.com response');
  }

  /**
   * Counts how many times each method of this class were called.
   *
   * @param string $method
   *   The method being called.
   */
  protected function collectCalledMethods(string $method): void {
    $state = \Drupal::state();
    $calls = $state->get('oe_search_test.service_mock_calls', [
      'applies' => 0,
      'getResponse' => 0,
    ]);
    $calls[$method]++;
    $state->set('oe_search_test.service_mock_calls', $calls);
  }

}
