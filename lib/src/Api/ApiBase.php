<?php

declare(strict_types = 1);

namespace OpenEuropa\EnterpriseSearchClient\Api;

use OpenEuropa\EnterpriseSearchClient\ApiClientInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base class for Enterprise Search API requests.
 */
abstract class ApiBase implements ApiInterface {

  /**
   * The API client.
   *
   * @var \OpenEuropa\EnterpriseSearchClient\ApiClientInterface
   */
  protected $client;

  /**
   * The request parameters.
   *
   * @var array
   */
  protected $parameters;

  /**
   * ApiBase constructor.
   *
   * @param \OpenEuropa\EnterpriseSearchClient\ApiClientInterface $client
   *   The API client.
   * @param array $parameters
   *   Extra parameters to be used for the request.
   */
  public function __construct(ApiClientInterface $client, array $parameters = []) {
    $this->client = $client;

    $this->parameters = $this->getOptionResolver()->resolve($parameters);
  }

  /**
   * Returns the option resolver configured with the API rules.
   *
   * @return \Symfony\Component\OptionsResolver\OptionsResolver $resolver
   *   The options resolver.
   */
  protected function getOptionResolver(): OptionsResolver {
    return new OptionsResolver();
  }

}
