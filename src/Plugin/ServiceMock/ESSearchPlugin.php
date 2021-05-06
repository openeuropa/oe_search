<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Plugin\ServiceMock;

use Drupal\Core\Plugin\PluginBase;
use Drupal\http_request_mock\ServiceMockPluginInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Intercepts any HTTP request made to /rest/search.
 *
 * @ServiceMock(
 *   id = "rest_search",
 *   label = @Translation("rest_search"),
 *   weight = 0,
 * )
 */
class ESSearchPlugin extends PluginBase implements ServiceMockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request, array $options): bool {
    return $request->getUri()->getHost() === 'api.com' && $request->getUri()->getPath() === '/search';
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(RequestInterface $request, array $options): ResponseInterface {
    $response = '[{"accessRestriction": true,"apiVersion": "string","children": [],"content": "string","contentType": "string","database": "string","databaseLabel": "string","groupById": "string","language": "string","metadata": {"additionalProp1": ["string"],"additionalProp2": ["string"],"additionalProp3": ["string"]},"pages": 0,"reference": "string","summary": "string","title": "string","url": "string","weight": 1.1}]';

    return new Response(200, [], $response);
  }

}
