<?php

declare(strict_types = 1);

namespace Drupal\oe_search_mock;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used for altering the responses of the ES mock.
 */
class EuropaSearchMockResponseEvent extends Event {

  /**
   * Event name.
   */
  const EUROPA_SEARCH_MOCK_RESPONSE_EVENT = 'europa_search_mock_response.event';

  /**
   * The request.
   *
   * @var \Psr\Http\Message\RequestInterface
   */
  protected $request;

  /**
   * The response.
   *
   * @var \GuzzleHttp\Psr7\Response
   */
  protected $response;

  /**
   * EuropaSearchMockResponseEvent constructor.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   * @param \GuzzleHttp\Psr7\Response $response
   *   The resources JSON data.
   */
  public function __construct(RequestInterface $request, Response $response) {
    $this->request = $request;
    $this->response = $response;
  }

  /**
   * Gets the response.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The response
   */
  public function getResponse(): Response {
    return $this->response;
  }

  /**
   * Sets the response.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   *   The response.
   */
  public function setResponse(Response $response): void {
    $this->response = $response;
  }

  /**
   * Gets the request.
   *
   * @return \Psr\Http\Message\RequestInterface
   *   The request.
   */
  public function getRequest(): RequestInterface {
    return $this->request;
  }

}
