<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Plugin\search_api\backend;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\Error;
use Drupal\oe_search\EntityMapper;
use Drupal\oe_search\Event\DocumentCreationEvent;
use Drupal\oe_search\IngestionDocument;
use Drupal\oe_search\QueryExpressionBuilder;
use Drupal\oe_search\Utility;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\ConditionGroup;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use OpenEuropa\EuropaSearchClient\Client;
use OpenEuropa\EuropaSearchClient\Contract\ClientInterface;
use OpenEuropa\EuropaSearchClient\Model\Document;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Europa Search backend for Search API.
 *
 * @SearchApiBackend(
 *   id = "search_api_europa_search",
 *   label = @Translation("Europa Search"),
 *   description = @Translation("Europa Search server Search API backend."),
 * )
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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
   * Europa Search API configuration keys and their Drupal correspondent.
   *
   * @var string[]
   */
  const CLIENT_CONFIG_KEYS = [
    'apiKey' => ['api_key'],
    'database' => ['database'],
    'infoApiEndpoint' => ['search', 'endpoint', 'info'],
    'searchApiEndpoint' => ['search', 'endpoint', 'search'],
    'facetApiEndpoint' => ['search', 'endpoint', 'facet'],
    'tokenApiEndpoint' => ['ingestion', 'endpoint', 'token'],
    'consumerKey' => ['consumer_key'],
    'consumerSecret' => ['consumer_secret'],
    'textIngestionApiEndpoint' => ['ingestion', 'endpoint', 'text'],
    'fileIngestionApiEndpoint' => ['ingestion', 'endpoint', 'file'],
    'deleteApiEndpoint' => ['ingestion', 'endpoint', 'delete'],
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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The query expression builder.
   *
   * @var \Drupal\oe_search\QueryExpressionBuilder
   */
  protected $queryExpressionBuilder;

  /**
   * The entity mapper service.
   *
   * @var \Drupal\oe_search\EntityMapper
   */
  protected $entityMapper;

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
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event service.
   * @param \Drupal\oe_search\QueryExpressionBuilder $query_expression_builder
   *   The query expression builder service.
   * @param \Drupal\oe_search\EntityMapper $entity_mapper
   *   The entity mapper service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, HttpClientInterface $http_client, Settings $settings, EventDispatcherInterface $event_dispatcher, QueryExpressionBuilder $query_expression_builder, EntityMapper $entity_mapper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->settings = $settings;
    $this->eventDispatcher = $event_dispatcher;
    $this->queryExpressionBuilder = $query_expression_builder;
    $this->entityMapper = $entity_mapper;
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
      $container->get('event_dispatcher'),
      $container->get('oe_search.query_expression_builder'),
      $container->get('oe_search.entity_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'api_key' => NULL,
      'database' => NULL,
      'search' => [
        'endpoint' => [
          'info' => NULL,
          'search' => NULL,
          'facet' => NULL,
        ],
      ],
      'ingestion' => [
        'enabled' => TRUE,
        'endpoint' => [
          'token' => NULL,
          'text' => NULL,
          'file' => NULL,
          'delete' => NULL,
        ],
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    try {
      $info = $this->getClient()->getInfo();
    }
    catch (\Throwable $e) {
      // If this fails for any reason, we log it without making the
      // application fail.
      $variables = Error::decodeException($e);
      $this->getLogger()->log(RfcLogLevel::ERROR, '%type: @message in %function (line %line of %file).', $variables);
    }

    return !empty($info);
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

    $form['database'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Database'),
      '#description' => $this->t('The database element correspond to a dataSource that contains the documents.'),
      '#default_value' => $configuration['database'],
      '#states' => [
        'required' => [':input[name="backend_config[ingestion][enabled]"]' => ['checked' => TRUE]],
      ],
    ];

    $form['search'] = [
      '#type' => 'details',
      '#title' => $this->t('Search & Info'),
      '#open' => TRUE,
    ];

    $form['search']['endpoint']['info'] = [
      '#type' => 'url',
      '#title' => $this->t('Info API endpoint'),
      '#required' => TRUE,
      '#default_value' => $configuration['search']['endpoint']['info'],
    ];

    $form['search']['endpoint']['search'] = [
      '#type' => 'url',
      '#title' => $this->t('Search API endpoint'),
      '#required' => TRUE,
      '#default_value' => $configuration['search']['endpoint']['search'],
    ];

    $form['search']['endpoint']['facet'] = [
      '#type' => 'url',
      '#title' => $this->t('Facets API endpoint'),
      '#default_value' => $configuration['search']['endpoint']['facet'],
    ];

    $form['ingestion'] = [
      '#type' => 'details',
      '#title' => $this->t('Ingestion'),
      '#open' => TRUE,
    ];

    $form['ingestion']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable ingestion'),
      '#default_value' => $configuration['ingestion']['enabled'],
    ];

    $states = [
      'required' => [':input[name="backend_config[ingestion][enabled]"]' => ['checked' => TRUE]],
      'enabled' => [':input[name="backend_config[ingestion][enabled]"]' => ['checked' => TRUE]],
    ];

    if ($missing_settings = $this->getMissingSettings()) {
      $form['ingestion']['settings'] = [
        '#type' => 'container',
        [
          '#theme' => 'status_messages',
          '#message_list' => [
            'error' => [
              [
                '#theme' => 'item_list',
                '#items' => $missing_settings,
                '#title' => $this->t('Missing <code>settings.php</code> entries:'),
              ],
            ],
          ],
        ],
        '#states' => [
          'visible' => [':input[name="backend_config[ingestion][enabled]"]' => ['checked' => TRUE]],
        ],
      ];
    }

    $form['ingestion']['endpoint']['token'] = [
      '#type' => 'url',
      '#title' => $this->t('Token API endpoint'),
      '#default_value' => $configuration['ingestion']['endpoint']['token'],
      '#states' => $states,
    ];

    $form['ingestion']['endpoint']['text'] = [
      '#type' => 'url',
      '#title' => $this->t('Text ingestion API endpoint'),
      '#default_value' => $configuration['ingestion']['endpoint']['text'],
      '#states' => $states,
    ];

    $form['ingestion']['endpoint']['file'] = [
      '#type' => 'url',
      '#title' => $this->t('File ingestion API endpoint'),
      '#default_value' => $configuration['ingestion']['endpoint']['file'],
      '#states' => $states,
    ];

    $form['ingestion']['endpoint']['delete'] = [
      '#type' => 'url',
      '#title' => $this->t('Delete API endpoint'),
      '#default_value' => $configuration['ingestion']['endpoint']['delete'],
      '#states' => $states,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items): array {
    if (!$this->isIngestionAvailable()) {
      return [];
    }

    $indexed = [];
    $result = NULL;

    /** @var \OpenEuropa\EuropaSearchClient\Model\Ingestion $result */
    foreach ($this->getDocuments($index, $items) as $item_id => $document) {
      try {
        if ($document->isTextIngestion()) {
          $result = $this->getClient()->ingestText(
            $document->getUrl(),
            $document->getContent(),
            [$document->getLanguage()],
            $document->getMetadata(),
            $document->getReference()
          );
        }
        elseif ($document->isFileIngestion()) {
          $result = $this->getClient()->ingestFile(
            $document->getUrl(),
            $document->getContent(),
            [$document->getLanguage()],
            $document->getMetadata(),
            $document->getReference()
          );
        }

        if ($result && $result->getReference()) {
          $indexed[] = $item_id;
        }
      }
      catch (\Exception $e) {
        $this->getLogger()->warning($e->getMessage());
      }
    }

    return $indexed;
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
      $references[] = Utility::createReference($index_id, $id);
    }

    foreach ($references as $reference) {
      try {
        $client->deleteDocument($reference);
      }
      catch (\Exception $e) {
        $this->getLogger()->warning($e->getMessage());
        throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
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

    $page = 1;
    while (TRUE) {
      $result = $this->getClient()->search(NULL, NULL, ['term' => ['SEARCH_API_SITE_HASH' => Utility::getSiteHash()]], NULL, NULL, $page++);
      $item_ids = array_map(function (Document $document) use ($index) {
        $destructed_reference = Utility::destructReference($document->getReference());
        $site_hash = $destructed_reference[0] ?? NULL;
        $index_id = $destructed_reference[1] ?? NULL;
        $item_id = $destructed_reference[2] ?? NULL;
        if (empty($site_hash) || empty($index_id) || empty($item_id) || $site_hash !== Utility::getSiteHash() || $index_id !== $index->id()) {
          return FALSE;
        }
        return $item_id;
      }, $result->getResults());
      $item_ids = array_filter($item_ids);

      if (empty($item_ids)) {
        break;
      }

      $this->deleteItems($index, $item_ids);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function search(QueryInterface $query): void {
    $results = $query->getResults();
    $page_number = NULL;
    $limit = $query->getOptions()['limit'] ?? NULL;

    // Set page number.
    if (!empty($query->getOptions()['offset']) && !empty($limit)) {
      $offset = (int) $query->getOptions()['offset'];
      $limit = (int) $query->getOptions()['limit'];
      $page_number = ($offset / $limit) + 1;
    }

    // Get text keys.
    $text = NULL;
    $keys = $query->getKeys();
    if (is_array($keys) && !empty($keys)) {
      if (isset($keys['#conjunction'])) {
        unset($keys['#conjunction']);
      }

      $text = implode(" ", $keys);

      // Only add "" in case of an expression composed of several words.
      if (str_word_count($text) > 1) {
        $text = "\"" . $text . "\"";
      }
    }
    elseif (is_string($keys) && !empty($keys) && str_word_count($keys) > 1) {
      // Only add "" in case of an expression composed of several words.
      $text = "\"" . $keys . "\"";
    }
    else {
      $text = $keys;
    }

    // Handle sorting.
    $sort_field = $sort_order = NULL;
    $sorts = $query->getSorts();
    if (!empty($sorts)) {
      foreach ($sorts as $field => $direction) {
        $field_name = Utility::getEsFieldName($field, $query);
        $sort_field[] = [$field_name, $direction];
      }
    }

    $index = $query->getIndex();
    $entity_load_mode = $index->getThirdPartySettings('oe_search')['europa_search_entity_mode'] ?? 'local';

    if ($entity_load_mode == 'local') {
      $query->addCondition(Utility::getEsFieldName('search_api_site_hash', $query), Utility::getSiteHash());
      $query->addCondition(Utility::getEsFieldName('search_api_index_id', $query), $index->id());
    }

    // Prepares query expression.
    $query_expression = $this->queryExpressionBuilder->prepareConditionGroup($query->getConditionGroup(), $query);

    // Execute search.
    try {
      $europa_response = $this->getClient()->search($text, NULL, $query_expression, $sort_field, $sort_order, $page_number, $limit);
    }
    catch (\Exception $e) {
      $this->getLogger()->error($e->getMessage());
      return;
    }

    $results->setResultCount($europa_response->getTotalResults());

    // Handle facets.
    // Only needed in case there are results.
    if ($results->getResultCount() && $available_facets = $query->getOption('search_api_facets')) {
      $facets = $this->getFacets($query, $available_facets, $text);
      $results->setExtraData('search_api_facets', $facets);
    }

    foreach ($europa_response->getResults() as $item) {
      $metadata = $item->getMetadata();

      $datasource_id = $metadata['SEARCH_API_DATASOURCE'][0];
      if (empty($datasource_id)) {
        continue;
      }

      $datasource_ids = $query->getIndex()->getDatasourceIds();
      if (!in_array($datasource_id, $datasource_ids)) {
        continue;
      }

      $datasource = $query->getIndex()->getDatasource($datasource_id);
      $item_id = ($entity_load_mode === 'local') ? $metadata['SEARCH_API_ID'][0] : $item->getUrl();
      $result_item = $this->getFieldsHelper()->createItem($index, $item_id, $datasource);
      // Used to allow queries to disable entity mapping.
      $transform_entity_set = $query->getOption('europa_search_transform_results', TRUE);
      if ($entity_load_mode === 'remote' && $transform_entity_set && $mapped_entity = $this->entityMapper->map($metadata, $query)) {
        $result_item->setOriginalObject($mapped_entity);
      }

      $results->addResultItem($result_item);
    }
  }

  /**
   * Handle facets.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query.
   * @param array $available_facets
   *   The configured facets for the index.
   * @param string|null $text
   *   Fulltext keys to search.
   *
   * @return array
   *   Facets keyed by facet_id.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  protected function getFacets(QueryInterface $query, array $available_facets = [], string $text = NULL): array {
    $facets = $response_facets = $or_response_facets = [];
    $query_expression = $this->queryExpressionBuilder->prepareConditionGroup($query->getConditionGroup(), $query);
    // Used for OR facets.
    $or_query_expression = $this->queryExpressionBuilder->prepareConditionGroup($query->getConditionGroup(), $query, TRUE);

    // Find which facets are OR facets.
    // We need this to support all results in OR facets when they
    // have active results.
    $or_facets = [];
    foreach ($query->getConditionGroup()->getConditions() as $condition) {
      if (!$condition instanceof ConditionGroup || $condition->getConjunction() !== 'OR') {
        continue;
      }

      $tags = $condition->getTags();
      foreach ($tags as $tag) {
        if (strpos($tag, 'facet:') === 0) {
          $facet_name = explode(':', $tag)[1];
          $or_facets[$facet_name] = $facet_name;
        }
      }
    }

    try {
      $display_fields = array_keys($available_facets);
      array_walk($display_fields, function (&$field) use ($query) {
        $field = Utility::getEsFieldName($field, $query);
      });
      $europa_response = $this->getClient()->getFacets($text, NULL, NULL, $query_expression, NULL, NULL, $display_fields);
    }
    catch (\Exception $e) {
      $this->getLogger()->error($e->getMessage());
      return $facets;
    }

    // Prepare response facets.
    foreach ($europa_response->getFacets() as $facet) {
      $facet_name = strtolower($facet->getRawName());
      $response_facets[$facet_name] = $or_response_facets[$facet_name] = $facet;
    }

    // Prepare OR response facets.
    // We only need to do this in the presence of active OR facets.
    if (count($or_facets) == 1) {
      try {
        $or_europa_response = $this->getClient()->getFacets($text, NULL, NULL, $or_query_expression);
      }
      catch (\Exception $e) {
        $this->getLogger()->error($e->getMessage());
        return $facets;
      }

      foreach ($or_europa_response->getFacets() as $facet) {
        $facet_name = strtolower($facet->getRawName());
        $or_response_facets[$facet_name] = $facet;
      }
    }

    // Loop through available facets to build the ones with results.
    foreach ($available_facets as $available_facet) {
      $facet_name = $available_facet['field'];
      if (!empty($response_facets[$facet_name])) {
        $response_facet = !empty($or_facets[$facet_name]) ? $or_response_facets[$facet_name] : $response_facets[$facet_name];
        $facet_results = [];
        foreach ($response_facet->getValues() as $value) {
          $filter = $value->getRawValue();
          $facet_results[] = [
            'filter' => '"' . $filter . '"',
            'count' => $value->getCount(),
          ];
        }

        $facets[$facet_name] = $facet_results;
      }
    }

    return $facets;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedFeatures() {
    $features = [
      'search_api_facets',
      'search_api_facets_operator_or',
    ];

    return $features;
  }

  /**
   * Returns a list of missing settings already formatted for display.
   *
   * @return string[]
   *   List of missing settings already formatted for display.
   */
  protected function getMissingSettings(): array {
    $missing_settings = [];
    $server_id = $this->getServer()->id() ?? 'your_server_machine_name';
    $settings_template = "\$settings['oe_search']['server']['%s']['%s'] = '%s';";
    foreach ($this->getConnectionSettings() as $setting => $value) {
      if (!$value) {
        $missing_settings[] = sprintf($settings_template, $server_id, $setting, $this->t('@name value...', [
          '@name' => str_replace('_', ' ', $setting),
        ]));
      }
    }
    return $missing_settings;
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

      // @todo Refactor this instantiation to a new plugin type in OEL-152.
      // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/OEL-152
      // @todo Replace \Http\Factory\Guzzle factories with the one provided by
      //   guzzlehttp/psr7:^2 when support for D9 is dropped.
      // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/OEL-194
      $this->client = new Client($this->httpClient, new RequestFactory(), new StreamFactory(), new UriFactory(), $configuration);
    }
    return $this->client;
  }

  /**
   * Gets the configuration and prepare for client usage.
   *
   * @return array
   *   The adapted configuration.
   */
  protected function getConfigurationForClient(): array {
    // Merge configuration and settings together.
    $configuration = $this->getConfiguration() + $this->getConnectionSettings();
    $client_configuration = [];
    foreach (static::CLIENT_CONFIG_KEYS as $key => $path) {
      if ($value = NestedArray::getValue($configuration, $path)) {
        $client_configuration[$key] = $value;
      }
    }
    return $client_configuration;
  }

  /**
   * Returns the values of connection settings.
   *
   * These connection info are considered sensitive and are depending on the
   * environment, so they are stored in `settings.php`, rather than config.
   *
   * @return array
   *   The values of connection settings.
   */
  protected function getConnectionSettings(): array {
    return array_map(function (string $setting): ?string {
      return $this->settings->get('oe_search')['server'][$this->getServer()->id()][$setting] ?? NULL;
    }, array_combine(static::CONNECTION_SETTINGS, static::CONNECTION_SETTINGS));
  }

  /**
   * Checks if ingestion requirements are satisfied.
   *
   * @return bool
   *   Check result.
   */
  protected function isIngestionAvailable(): bool {
    $ingestion_configuration = $this->getConfiguration()['ingestion'];

    // Ingestion has been explicitly disabled.
    if (!$ingestion_configuration['enabled']) {
      return FALSE;
    }

    // At least one ingestion endpoint is missing.
    foreach ($ingestion_configuration['endpoint'] as $url) {
      if (empty($url)) {
        return FALSE;
      }
    }

    // At least one of settings is missing.
    if (array_keys(array_filter($this->getConnectionSettings())) !== static::CONNECTION_SETTINGS) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Prepares documents from search API index items for ingestion.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The Search API index.
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   An array of items to get documents for.
   *
   * @return \Drupal\oe_search\IngestionDocument[]
   *   An array of documents.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  protected function getDocuments(IndexInterface $index, array $items): array {
    $documents = [];

    foreach ($items as $id => $item) {
      if (!$entity = $this->getEntity($item)) {
        continue;
      }

      // Non-publishable entities still can be indexed by explicitly subscribing
      // to DocumentCreationEvent and use IngestionDocument::setCanBeIngested().
      // @todo Reevaluate this logic in OEL-218.
      // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/OEL-218
      $can_be_ingested = $entity instanceof EntityPublishedInterface ? $entity->isPublished() : FALSE;

      $document = (new IngestionDocument())
        ->setContent($entity->label())
        ->setLanguage($item->getLanguage())
        ->setReference(Utility::createReference($index->id(), $id))
        ->setCanBeIngested($can_be_ingested);

      // Entities without a canonical URL are able to set an arbitrary one by
      // subscribing to DocumentCreationEvent.
      if ($entity->getEntityType()->hasLinkTemplate('canonical')) {
        // Default to entity's canonical URL.
        $document->setUrl($entity->toUrl()->setAbsolute()->toString());
      }

      // Guarantee special fields are ingested uppercase.
      $special_fields_uppercase = [];
      $special_fields = $this->getSpecialFields($index, $item);
      foreach ($special_fields as $name => $field) {
        $special_fields_uppercase[strtoupper($name)] = $field;
      }

      $item_fields = $special_fields_uppercase + $item->getFields();
      foreach ($item_fields as $name => $field) {
        $document->addMetadata($name, $field->getValues(), $field->getType());
      }

      // Allow third-party to alter the document being ingested.
      $event = (new DocumentCreationEvent())
        ->setDocument($document)
        ->setItem($item)
        ->setEntity($entity);
      // @todo Remove 1st argument when dropping support for Drupal 8.9.
      $this->eventDispatcher->dispatch($event, DocumentCreationEvent::class);

      if (!$document->getUrl()) {
        $document->setCanBeIngested(FALSE);
      }

      if ($document->canBeIngested()) {
        $documents[$id] = $document;
      }
    }

    return $documents;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSpecialFields(IndexInterface $index, ItemInterface $item = NULL): array {
    $fields = parent::getSpecialFields($index, $item);

    $field_info = [
      'type' => 'string',
      'original type' => 'string',
    ];

    $fields['search_api_site_hash'] = $this->getFieldsHelper()
      ->createField($index, 'search_api_site_hash', $field_info)
      ->setValues([Utility::getSiteHash()]);
    $fields['search_api_index_id'] = $this->getFieldsHelper()
      ->createField($index, 'search_api_index_id', $field_info)
      ->setValues([$index->id()]);

    return $fields;
  }

  /**
   * Extracts the content entity out of a give Search API item.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The Search API item.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The content entity or NULL if none.
   */
  protected function getEntity(ItemInterface $item): ?ContentEntityInterface {
    if ($item->getOriginalObject() === NULL) {
      return NULL;
    }

    $entity = $item->getOriginalObject()->getValue();
    // Module limitation: Only supports content entity datasources.
    if (!$entity instanceof ContentEntityInterface) {
      return NULL;
    }

    return $entity;
  }

}
