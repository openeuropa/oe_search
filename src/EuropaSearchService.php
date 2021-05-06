<?php

namespace Drupal\oe_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Http\Adapter\Guzzle6\Client as HttpClient;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use OpenEuropa\EuropaSearchClient\Api\IngestionApi;
use OpenEuropa\EuropaSearchClient\Api\SearchApi;
use OpenEuropa\EuropaSearchClient\Client;
use OpenEuropa\EuropaSearchClient\ClientInterface;

/**
 * European Commission Europa Search.
 */
class EuropaSearchService {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a ProviderRepository instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory
  ) {
    $this->config = $config_factory->get('oe_search.server');
  }

  /**
   * Return Ingestion API.
   */
  public function ingestionApi() {
    $client = $this->getClient();
    $api = new IngestionApi($client);

    return $api;
  }

  /**
   * Return Search results.
   */
  public function searchApi() {
    $client = $this->getClient();
    $api = new SearchApi($client);
    $search = $api->search();

    return $search->getResults();
  }

  /**
   * Returns a client instance.
   *
   * @return \OpenEuropa\EuropaSearchClient\ClientInterface
   *   The client.
   */
  protected function getClient(): ClientInterface {
    $configuration = $this->config->getRawData();

    // Normalise configuration name from Drupal standards.
    $configuration['apiKey'] = $configuration['api_key'];
    unset($configuration['api_key']);
    unset($configuration['_core']);

    // @todo Make the client available through a service.
    $guzzle_psr = new HttpClient(\Drupal::service('http_client'));
    $client = new Client($guzzle_psr, new RequestFactory(), new StreamFactory(), $configuration);

    return $client;
  }

}
