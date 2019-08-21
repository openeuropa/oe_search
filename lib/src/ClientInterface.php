<?php

declare(strict_types = 1);

namespace OpenEuropa\EnterpriseSearchClient;

use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Interface for clients that interact with Enterprise Search API.
 */
interface ClientInterface {

  /**
   * Returns the HTTP client that is used for requests.
   *
   * @return \Psr\Http\Client\ClientInterface The HTTP client.
   *   The HTTP client.
   */
  public function getHttpClient(): HttpClientInterface;

  /**
   * Returns the request factory.
   *
   * @return \Psr\Http\Message\RequestFactoryInterface
   *   The request factory.
   */
  public function getRequestFactory(): RequestFactoryInterface;

  /**
   * Returns the serializer.
   *
   * @return \Symfony\Component\Serializer\SerializerInterface
   *   The serializer.
   */
  public function getSerializer(): SerializerInterface;

  /**
   * Returns the stream factory.
   *
   * @return \Psr\Http\Message\StreamFactoryInterface
   *   The request factory.
   */
  public function getStreamFactory(): StreamFactoryInterface;

  /**
   * Returns the client configuration.
   *
   * @param string|null $name
   *   The configuration name. Returns all the configuration if empty.
   *
   * @return mixed|array
   *   The client configuration.
   */
  public function getConfiguration(string $name = null);

}
