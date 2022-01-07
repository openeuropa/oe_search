<?php

declare(strict_types = 1);

namespace Drupal\oe_search_mock\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Overrides the Europa Search config to use the endpoint of the ES mock server.
 *
 * If the OE Search Mock module is not enabled this overrider
 * will have no effect since the config factory will never ask us to
 * override the module configuration.
 */
class EuropaSearchMockServerConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * Europa Search domain endpoint.
   *
   * @var string
   */
  const ENDPOINT_DOMAIN = 'example.com';

  /**
   * Europa Search info endpoint.
   *
   * @var string
   */
  const ENDPOINT_INFO = '/search-api/acc/info';

  /**
   * Europa Search search endpoint.
   *
   * @var string
   */
  const ENDPOINT_SEARCH = '/search-api/acc/rest/search';

  /**
   * Europa Search facet endpoint.
   *
   * @var string
   */
  const ENDPOINT_FACET = '/search-api/acc/rest/facet';

  /**
   * Europa Search token endpoint.
   *
   * @var string
   */
  const ENDPOINT_TOKEN = '/token';

  /**
   * Europa Search ingestion text endpoint.
   *
   * @var string
   */
  const ENDPOINT_INGESTION_TEXT = '/ingestion-api/acc/rest/ingestion/text';

  /**
   * Europa Search ingestion file endpoint.
   *
   * @var string
   */
  const ENDPOINT_INGESTION_FILE = '/rest/ingestion/';

  /**
   * Europa Search ingestion delete endpoint.
   *
   * @var string
   */
  const ENDPOINT_INGESTION_DELETE = '/ingestion-api/acc/rest/document';

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('search_api.server.europa_search_server', $names)) {
      $mock_domain = 'http://' . self::ENDPOINT_DOMAIN;
      $overrides = [
        'search_api.server.europa_search_server' => [
          'status' => TRUE,
          'backend_config' => [
            'api_key' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            'database' => 'XXXX_XXX',
            'search' => [
              'endpoint' => [
                'info' => $mock_domain . self::ENDPOINT_INFO,
                'search' => $mock_domain . self::ENDPOINT_SEARCH,
                'facet' => $mock_domain . self::ENDPOINT_FACET,
              ],
            ],
            'ingestion' => [
              'enabled' => TRUE,
              'endpoint' => [
                'token' => $mock_domain . self::ENDPOINT_TOKEN,
                'text' => $mock_domain . self::ENDPOINT_INGESTION_TEXT,
                'file' => $mock_domain . self::ENDPOINT_INGESTION_FILE,
                'delete' => $mock_domain . self::ENDPOINT_INGESTION_DELETE,
              ],
            ],
          ],
        ],
      ];
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ConfigOverriderEuropaSearch';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

}
