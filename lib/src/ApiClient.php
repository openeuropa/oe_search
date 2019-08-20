<?php

declare(strict_types = 1);

namespace OpenEuropa\EnterpriseSearchClient;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * The client to interface with the Enterprise Search API calls.
 */
class ApiClient implements ApiClientInterface {

  /**
   * The client to send HTTP requests.
   *
   * @var \Psr\Http\Client\ClientInterface
   */
  protected $client;

  /**
   * Extra client configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The request factory.
   *
   * @var \Psr\Http\Message\RequestFactoryInterface
   */
  protected $requestFactory;

  /**
   * ApiClient constructor.
   *
   * @param \Psr\Http\Client\ClientInterface $client
   *   The client to send HTTP requests.
   * @param \Psr\Http\Message\RequestFactoryInterface $requestFactory
   *   The request factory.
   * @param array $configuration
   *   The client configuration.
   */
  public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory, array $configuration = []) {
    $this->client = $client;
    $this->requestFactory = $requestFactory;
    $this->configuration = $configuration;
  }

  /**
   * @inheritDoc
   */
  public function getHttpClient(): ClientInterface {
    return $this->client;
  }

}
