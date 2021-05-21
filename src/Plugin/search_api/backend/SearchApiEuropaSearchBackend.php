<?php

declare(strict_types=1);

namespace Drupal\oe_search\Plugin\search_api\backend;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Site\Settings;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\QueryInterface;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use OpenEuropa\EuropaSearchClient\Client;
use OpenEuropa\EuropaSearchClient\Contract\ClientInterface;
use OpenEuropa\EuropaSearchClient\Model\Document;
use Psr\Http\Client\ClientInterface as PsrClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Europa Search backend for Search API.
 *
 * @SearchApiBackend(
 *   id = "search_api_europa_search",
 *   label = @Translation("Europa Search"),
 *   description = @Translation("Index items using Europa Search search server."),
 * )
 */
class SearchApiEuropaSearchBackend extends BackendPluginBase implements PluginFormInterface {

  use PluginFormTrait {
    submitConfigurationForm as traitSubmitConfigurationForm;
  }

  /**
   * Connection info stored as Drupal settings.
   *
   * These connection information are sensitive data and may be different on
   * each environment (i.e. the acceptance machine may use an Europa Search
   * sandbox instance). For this reason we'll store them as settings in
   * `settings.php` file rather than config storage. Storing them in config
   * would be a security concern, as configuration may end up being committed in
   * VCS and exposed publicly.
   *
   * @var string[]
   */
  const CONNECTION_SETTINGS = [
    'consumer_key',
    'consumer_secret',
  ];

  /**
   * Europa Search Api configuration keys.
   *
   * @var string[]
   */
  const CLIENT_CONFIG_KEYS = [
    'apiKey',
    'database',
    'searchApiEndpoint',
    'infoApiEndpoint',
    'facetsApiEndpoint',
    'tokenApiEndpoint',
    'textIngestionApiEndpoint',
    'fileIngestionApiEndpoint',
    'deleteApiEndpoint',
    'consumerKey',
    'consumerSecret',
  ];

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The Europa Search client instance.
   *
   * @var \OpenEuropa\EuropaSearchClient\Contract\ClientInterface
   */
  protected $client;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, HttpClientInterface $http_client, Settings $settings, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->settings = $settings;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('settings'),
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'api_key' => NULL,
      'enable_ingestion' => TRUE,
      'database' => NULL,
      'text_ingestion_api_endpoint' => NULL,
      'file_ingestion_api_endpoint' => NULL,
      'delete_api_endpoint'  => NULL,
      'token_api_endpoint' => NULL,
      'search_api_endpoint' => NULL,
      'facets_api_endpoint' => NULL,
      'info_api_endpoint' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    // @todo Perform a ping as soon as the functionality is available.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $configuration = $this->getConfiguration();

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The API key is a unique key generated by the search team. It ties your application to a specific behaviour (allowed field names, security details, display templates, field translations, etc).'),
      '#required' => TRUE,
      '#default_value' => $configuration['api_key'],
    ];

    $form['enable_ingestion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable ingestion'),
      '#description' => $this->t('All ingestion configuration will be required'),
      '#default_value' => $configuration['enable_ingestion'],
    ];

    $form['database'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Database'),
      '#description' => $this->t('The database element correspond to a dataSource that contains the documents.'),
      '#default_value' => $configuration['database'],
      '#states' => [
        'required' => [':input[name="backend_config[enable_ingestion]"]' => ['checked' => TRUE]],
      ],
    ];

    $form['text_ingestion_api_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Text Ingestion API endpoint'),
      '#description' => $this->t('The URL of the endpoint where the Text Ingestion API is available.'),
      '#default_value' => $configuration['text_ingestion_api_endpoint'],
      '#states' => [
        'required' => [':input[name="backend_config[enable_ingestion]"]' => ['checked' => TRUE]],
      ],
    ];

    $form['file_ingestion_api_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('File Ingestion API endpoint'),
      '#description' => $this->t('The URL of the endpoint where the File Ingestion API is available.'),
      '#default_value' => $configuration['file_ingestion_api_endpoint'],
      '#states' => [
        'required' => [':input[name="backend_config[enable_ingestion]"]' => ['checked' => TRUE]],
      ],
    ];

    $form['delete_api_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Delete API endpoint'),
      '#description' => $this->t('The URL of the endpoint where the Delete API is available.'),
      '#default_value' => $configuration['delete_api_endpoint'],
      '#states' => [
        'required' => [':input[name="backend_config[enable_ingestion]"]' => ['checked' => TRUE]],
      ],
    ];

    $form['token_api_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Token API endpoint'),
      '#description' => $this->t('The URL of the endpoint where the Token API is available.'),
      '#default_value' => $configuration['token_api_endpoint'],
      '#states' => [
        'required' => [':input[name="backend_config[enable_ingestion]"]' => ['checked' => TRUE]],
      ],
    ];

    $form['search_api_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Search API endpoint'),
      '#description' => $this->t('The URL of the endpoint where the Search API is available.'),
      '#required' => TRUE,
      '#default_value' => $configuration['search_api_endpoint'],
    ];

    $form['facets_api_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Facets API endpoint'),
      '#description' => $this->t('The URL of the endpoint where the Facets API is available.'),
      '#default_value' => $configuration['facets_api_endpoint'],
    ];

    $form['info_api_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Info API endpoint'),
      '#description' => $this->t('The URL of the endpoint where the Info API is available.'),
      '#required' => TRUE,
      '#default_value' => $configuration['info_api_endpoint'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getValue('enable_ingestion') === FALSE) {
      return;
    }

    $missing_settings = [];
    $consumer_settings_template = "\$settings['oe_search']['backend']['%s']['%s'] = '%s';";

    foreach ($this->getConnectionSettings() as $setting => $value) {
      if (!$value) {
        $missing_settings[] = sprintf($consumer_settings_template, $this->getServer()->id(), $setting, $this->t('@name value...', [
          '@name' => str_replace('_', ' ', $setting),
        ]));
      }
    }

    if (!$missing_settings) {
      return;
    }

    $error = [
      [
        '#markup' => $this->t('Missing <code>settings.php</code> entries:'),
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => implode("\n", $missing_settings),
      ],
    ];

    $element = [];
    $form_state->setErrorByName($element, $error);
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items): array {
    if (!$this->isIngestionAvailable()) {
      return [];
    }

    $documents = $this->getDocuments($index, $items);
    $client = $this->getClient();
    $indexes = [];

    /** @var \OpenEuropa\EuropaSearchClient\Model\Ingestion $result */
    /** @var \OpenEuropa\EuropaSearchClient\Model\Document $document */
    foreach ($documents as $item_id => $document) {
      try {
        $result = $client->ingestText(
          $document->getUrl(),
          $document->getContent(),
          [$document->getLanguage()],
          // @todo add metadata.
          NULL,
          $document->getReference()
        );

        if ($result->getReference()) {
          $indexes[] = $item_id;
        }
      }
      catch (\Exception $e) {
        $this->getLogger()->warning($e->getMessage());
      }
    }

    return $indexes;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $item_ids): void {
    if (!$this->isIngestionAvailable()) {
      return;
    }

    $client = $this->getClient();
    $index_id = $index->id();
    $references = [];

    foreach ($item_ids as $id) {
      $references[] = $this->createReference(NULL, $index_id, $id);
    }

    foreach ($references as $reference) {
      try {
        $client->deleteDocument($reference);
      }
      catch (\Exception $e) {
        $this->getLogger()->warning($e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL): void {
    if (!$this->isIngestionAvailable()) {
      return;
    }

    $client = $this->getClient();
    /** @var \OpenEuropa\EuropaSearchClient\Model\SearchResult $result */
    $result = $client->search();

    $item_ids = array_map(function (Document $document) {
      return $document->getReference();
    }, $result->getResults());

    $this->deleteItems($index, $item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query): void {
  }

  /**
   * Returns an Europa Search client instance.
   *
   * @return \OpenEuropa\EuropaSearchClient\Contract\ClientInterface
   *   The client.
   */
  protected function getClient(): ClientInterface {
    if (!isset($this->client)) {
      $configuration = $this->getConfigurationForClient();
      // The client uses PSR standards.
      if (!$this->httpClient instanceof PsrClient) {
        $this->httpClient = new GuzzleAdapter($this->httpClient);
      }
      // @todo Refactor this instantiation to a new plugin type in OEL-152.
      // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/OEL-152
      $this->client = new Client($this->httpClient, new RequestFactory(), new StreamFactory(), new UriFactory(), $configuration);
    }
    return $this->client;
  }

  /**
   * Get the configuration and prepare for client usage.
   *
   * @return array
   *   The adapted configuration.
   */
  protected function getConfigurationForClient(): array {
    // Merge configuration and settings together.
    $configuration = $this->getConfiguration() + $this->getConnectionSettings();
    // The client uses the snake case version of connection data identifiers.
    $snake_converter = new CamelCaseToSnakeCaseNameConverter();
    $keys = array_map(function ($key) use ($snake_converter): string {
      return $snake_converter->denormalize($key);
    }, array_keys($configuration));
    $configuration = array_combine($keys, $configuration);

    return array_filter($configuration, function ($key): bool {
      return in_array($key, static::CLIENT_CONFIG_KEYS);
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * Returns the values of connection settings.
   *
   * These connection data is considered sensitive and depending on the
   * environment, thus is stored in `settings.php`, rather than config store.
   *
   * @return array
   *   The values of connection settings.
   */
  protected function getConnectionSettings(): array {
    return array_map(function (string $setting): ?string {
      return $this->settings->get('oe_search')['backend'][$this->getServer()->id()][$setting] ?? NULL;
    }, array_combine(static::CONNECTION_SETTINGS, static::CONNECTION_SETTINGS));
  }

  /**
   * Checks if ingestion requirements are satisfied.
   *
   * @return bool
   *   Check result.
   */
  protected function isIngestionAvailable(): bool {
    $configuration = $this->getConfiguration() + $this->getConnectionSettings();

    if (empty($configuration['api_key'])) {
      return FALSE;
    }
    if (empty($configuration['database'])) {
      return FALSE;
    }
    if (empty($configuration['text_ingestion_api_endpoint'])) {
      return FALSE;
    }
    if (empty($configuration['file_ingestion_api_endpoint'])) {
      return FALSE;
    }
    if (empty($configuration['delete_api_endpoint'])) {
      return FALSE;
    }
    if (empty($configuration['token_api_endpoint'])) {
      return FALSE;
    }
    if (!array_keys(array_filter($this->getConnectionSettings())) === static::CONNECTION_SETTINGS) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Prepare documents from search api index items for ingestion.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The Search API index.
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   An array of items to get documents for.
   *
   * @return array
   *   An array of solr documents.
   */
  protected function getDocuments(IndexInterface $index, array $items): array {
    $documents = [];
    $index_id = $index->id();
    $languages = $this->languageManager->getLanguages();

    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    foreach ($items as $id => $item) {
      $document = new Document();
      $language_id = $item->getLanguage();
      $document->setUrl($item->getOriginalObject()->getValue()->toUrl()->setAbsolute()->toString());
      $document->setContent($item->getOriginalObject()->getValue()->label());
      $document->setLanguage($language_id);
      $document->setReference($this->createReference(NULL, $index_id, $id));
      $documents[$id] = $document;
    }

    return $documents;
  }

  /**
   * Creates an ID used as the unique identifier at the Europa Search server.
   *
   * This has to consist of both index and item ID. Optionally, the site hash is
   * also included.
   *
   * @param string $site_hash
   *   The site hash.
   * @param string $index_id
   *   The index ID.
   * @param string|int $item_id
   *   The item ID.
   *
   * @return string
   *   A unique identifier for the given item.
   */
  protected function createReference($site_hash, $index_id, $item_id) {
    return "$site_hash-$index_id-$item_id";
  }

}
