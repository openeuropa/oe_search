<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Plugin\ServiceMock;

use Drupal\Core\Plugin\PluginBase;
use Drupal\http_request_mock\ServiceMockPluginInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Intercepts any HTTP request made to /rest/ingestion/text.
 *
 * @ServiceMock(
 *   id = "rest_ingestion",
 *   label = @Translation("rest_ingestion"),
 *   weight = 1,
 * )
 */
class ESIngestionPlugin extends PluginBase implements ServiceMockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request, array $options): bool {
    return $request->getUri()->getHost() === 'api.com' && $request->getUri()->getPath() === '/ingestion';
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(RequestInterface $request, array $options): ResponseInterface {
    $response = '{"apiVersion": "string","reference": "string","trackingId": "string"}';

    return new Response(200, [], $response);
  }

}
