<?php

declare(strict_types = 1);

namespace Drupal\oe_search_test\Plugin\ServiceMock;

use Drupal\Core\Plugin\PluginBase;
use Drupal\http_request_mock\ServiceMockPluginInterface;
use Drupal\oe_search\Utility;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Intercepts any HTTP request made to example.com.
 *
 * @ServiceMock(
 *   id = "europa_search_server_response",
 *   label = @Translation("Europa Search mocked server responses for testing."),
 *   weight = -1,
 * )
 */
class EuropaSearchServerResponse extends PluginBase implements ServiceMockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request, array $options): bool {
    $this->collectCalledMethods($request->getUri()->getPath(), __FUNCTION__);
    return $request->getUri()->getHost() === 'example.com';
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(RequestInterface $request, array $options): ResponseInterface {
    $path = $request->getUri()->getPath();
    $this->collectCalledMethods($path, __FUNCTION__);
    $this->collectRequests($path, $request);

    switch ($path) {
      case '/token':
        $response = $this->getTokenResponse();
        break;

      case '/ingest/text':
        $response = $this->getIngestTextResponse();
        break;

      case '/ingest/delete':
        $response = $this->getDeleteResponse();
        break;

      case '/search/search':
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
   * Collects the requests received.
   *
   * @param string $path
   *   The request path.
   * @param \Psr\Http\Message\RequestInterface $request
   *   The received request.
   */
  protected function collectRequests(string $path, RequestInterface $request): void {
    $state = \Drupal::state();
    $requests = $state->get('oe_search_test.service_mock_requests', []);
    $requests[$path][] = $request;
    $state->set('oe_search_test.service_mock_requests', $requests);
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

  /**
   * Get mocked delete response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getDeleteResponse(): ResponseInterface {
    return new Response(200, [], json_encode([
      'apiVersion' => '2.67',
      'reference' => 'foo',
      'trackingId' => 'bar',
    ]));
  }

  /**
   * Get mocked search response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The mocked response.
   */
  protected function getSearchResponse(): ResponseInterface {
    $index_id = 'europa_search_index';

    return new Response(200, [], json_encode([
      'apiVersion' => '2.69',
      'terms' => '',
      'responseTime' => 44,
      'totalResults' => 5,
      'pageNumber' => 1,
      'pageSize' => 10,
      'sort' => 'title:ASC',
      'groupByField' => NULL,
      'queryLanguage' => [
        'language' => 'en',
        'probability' => 0.0,
      ],
      'spellingSuggestion' => '',
      'bestBets' => [],
      'results' => [
        [
          'apiVersion' => '2.69',
          'reference' => Utility::createReference($index_id, 'entity:entity_test_mulrev_changed/1:en'),
          'url' => 'http://example.com/ref1',
          'title' => NULL,
          'contentType' => 'text/plain',
          'language' => 'en',
          'databaseLabel' => 'ACME',
          'database' => 'ACME',
          'summary' => NULL,
          'weight' => 9.849739,
          'groupById' => '3',
          'content' => 'A coordination platform',
          'accessRestriction' => FALSE,
          'pages' => NULL,
          'metadata' => [],
          'children' => [],
        ],
        [
          'apiVersion' => '2.69',
          'reference' => Utility::createReference($index_id, 'entity:entity_test_mulrev_changed/2:en'),
          'url' => 'http://example.com/ref2',
          'title' => NULL,
          'contentType' => 'text/plain',
          'language' => 'en',
          'databaseLabel' => 'ACME',
          'database' => 'ACME',
          'summary' => NULL,
          'weight' => 9.849739,
          'groupById' => '3',
          'content' => 'A coordination platform',
          'accessRestriction' => FALSE,
          'pages' => NULL,
          'metadata' => [],
          'children' => [],
        ],
        [
          'apiVersion' => '2.69',
          'reference' => Utility::createReference($index_id, 'entity:entity_test_mulrev_changed/3:en'),
          'url' => 'http://example.com/ref3',
          'title' => NULL,
          'contentType' => 'text/plain',
          'language' => 'en',
          'databaseLabel' => 'ACME',
          'database' => 'ACME',
          'summary' => NULL,
          'weight' => 9.849739,
          'groupById' => '3',
          'content' => 'A coordination platform',
          'accessRestriction' => FALSE,
          'pages' => NULL,
          'metadata' => [],
          'children' => [],
        ],
        [
          'apiVersion' => '2.69',
          'reference' => Utility::createReference($index_id, 'entity:entity_test_mulrev_changed/4:en'),
          'url' => 'http://example.com/ref4',
          'title' => NULL,
          'contentType' => 'text/plain',
          'language' => 'en',
          'databaseLabel' => 'ACME',
          'database' => 'ACME',
          'summary' => NULL,
          'weight' => 9.849739,
          'groupById' => '3',
          'content' => 'A coordination platform',
          'accessRestriction' => FALSE,
          'pages' => NULL,
          'metadata' => [],
          'children' => [],
        ],
        [
          'apiVersion' => '2.69',
          'reference' => Utility::createReference($index_id, 'entity:entity_test_mulrev_changed/5:en'),
          'url' => 'http://example.com/ref5',
          'title' => NULL,
          'contentType' => 'text/plain',
          'language' => 'en',
          'databaseLabel' => 'ACME',
          'database' => 'ACME',
          'summary' => NULL,
          'weight' => 9.849739,
          'groupById' => '3',
          'content' => 'A coordination platform',
          'accessRestriction' => FALSE,
          'pages' => NULL,
          'metadata' => [],
          'children' => [],
        ],
      ],
      'warnings' => [],
    ]));
  }

}
