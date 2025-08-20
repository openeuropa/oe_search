<?php

declare(strict_types=1);

namespace Drupal\oe_search;

use OpenEuropa\EuropaSearchClient\Client;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Extends from the client altering the token service.
 */
class CacheableClient extends Client {

  /**
   * {@inheritDoc}
   */
  protected function createContainer(HttpClientInterface $httpClient, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory, UriFactoryInterface $uriFactory): void {
    parent::createContainer($httpClient, $requestFactory, $streamFactory, $uriFactory);
    $this->container->extend('token')->setConcrete(CacheableTokenEndpoint::class);
  }

}
