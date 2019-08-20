<?php

declare(strict_types = 1);

namespace OpenEuropa\EnterpriseSearchClient;

use Psr\Http\Client\ClientInterface;

/**
 * Interface for clients that interact with Enterprise Search API.
 */
interface ApiClientInterface {

  /**
   * Returns the HTTP client that is used for requests.
   *
   * @return \Psr\Http\Client\ClientInterface The HTTP client.
   *   The HTTP client.
   */
  public function getHttpClient(): ClientInterface;

}
