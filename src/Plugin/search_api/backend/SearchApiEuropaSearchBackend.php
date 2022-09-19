<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Plugin\search_api\backend;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Site\Settings;
use Drupal\node\Entity\Node;
use Drupal\oe_search\Event\DocumentCreationEvent;
use Drupal\oe_search\IngestionDocument;
use Drupal\oe_search\Utility;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\Condition;
use Drupal\search_api\Query\ConditionGroup;
use Drupal\search_api\Query\QueryInterface;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use Http\Factory\Guzzle\UriFactory;
use OpenEuropa\EuropaSearchClient\Client;
use OpenEuropa\EuropaSearchClient\Contract\ClientInterface;
use OpenEuropa\EuropaSearchClient\Model\Document;
use Psr\Http\Client\ClientInterface as PsrClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventService;

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
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_service
   *   The event service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, HttpClientInterface $http_client, Settings $settings, ContainerAwareEventDispatcher $event_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->settings = $settings;
    $this->eventService = $event_service;
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
      $container->get('event_dispatcher')
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
    $info = $this->getClient()->getInfo();
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
   */
  public function search(QueryInterface $query): void {
    $results = $query->getResults();
    $page_number = NULL;

    // Set page number.
    if (!empty($query->getOptions()['offset']) && !empty($query->getOptions()['limit'])) {
      $offset = $query->getOptions()['offset'];
      $limit = $query->getOptions()['limit'];
      $page_number = ($offset / $limit) + 1;
    }

    // Get text keys.
    $text = NULL;
    if (!empty($query->getKeys())) {
      $text = $query->getKeys()[0];
    }

    // Get term conditions.
    $query_conjuction = $query->getConditionGroup()->getConjunction() ?? NULL;
    $terms_expression = $this->buildConditionGroup($query_conjuction, $query->getConditionGroup()->getConditions());

    // Handle sorting.
    // @todo Multifield sorting is not supported yet by the client.
    $sort_field = $sort_order = NULL;
    $sorts = $query->getSorts();
    if (!empty($sorts)) {
      $sort_field = strtoupper(key($sorts));
      $sort_order = reset($sorts);
    }

    // Handle facets.
    if ($available_facets = $query->getOption('search_api_facets')) {
      $facets = $this->getFacets($available_facets, $text, $terms_expression);
      $results->setExtraData('search_api_facets', $facets);
    }

    // In case of local entities.
    /*
    $terms_expression = [
    'bool' => [
    'must' => [
    'term' => [
    'SEARCH_API_SITE_HASH' => Utility::getSiteHash()
    ]
    ]
    ]
    ];
     */

    // Execute search.
    $europa_response = $this->getClient()->search($text, NULL, $terms_expression, $sort_field, $sort_order, $page_number);
    $results->setResultCount($europa_response->getTotalResults());
    // @todo Adapt for generic entities.
    $index_fields = $query->getIndex()->getFieldsByDatasource('entity:node');

    foreach ($europa_response->getResults() as $item) {
      $metadata = $item->getMetadata();
      $datasource = $query->getIndex()->getDatasource($metadata['SEARCH_API_DATASOURCE'][0]);
      $item_id = $item->getUrl();
      $item_id = $metadata['SEARCH_API_ID'][0];
      $result_item = $this->getFieldsHelper()->createItem($query->getIndex(), $item_id, $datasource);

      try {
        /*
        $entity = $this->getMappedEntity($metadata, $index_fields);
        // Needed to avoid loading entity in translations.
        $entity->in_preview = TRUE;
        $object = EntityAdapter::createFromEntity($entity);
        $result_item->setOriginalObject($object);
         */
        $results->addResultItem($result_item);
      }
      catch (EntityStorageException $e) {

      }
    }
  }

  /**
   * Builds a condition group for ES.
   *
   * @param string $query_conjunction
   *   The query conjunction.
   * @param array $query_conditions
   *   The query conditions.
   *
   * @return array
   *   The condition group to be used in ES.
   */
  protected function buildConditionGroup(string $query_conjunction, array $query_conditions) : array {
    $conditions = [];

    if (empty($query_conjunction)) {
      return $conditions;
    }

    foreach ($query_conditions as $condition) {
      if ($condition instanceof Condition) {
        if (empty($condition->getValue())) {
          continue;
        }
        $conditions[] = $this->addCondition($condition);

      }
      elseif ($condition instanceof ConditionGroup) {
        $conditions[] = $this->buildConditionGroup($condition->getConjunction(), $condition->getConditions());
      }
    }

    // We don't need a condition group for a single condition.
    if (count($conditions) == 1) {
      return $conditions[0];
    }

    // @todo Support for negation operator (must_not).
    $conjuction = ($query_conjunction == 'AND') ? 'must' : 'should';
    return [
      'bool' => [
        $conjuction => $conditions,
      ],
    ];
  }

  /**
   * Adds condition to the condition group.
   *
   * @param \Drupal\search_api\Query\Condition $condition
   *   The query condition.
   *
   * @return array
   *   The array with resulting condition.
   */
  protected function addCondition(Condition $condition) {
    if ($condition->getOperator() == '>') {
      return ['range' => [strtoupper($condition->getField()) => ['gt' => $condition->getValue()]]];
    }
    elseif ($condition->getOperator() == '>=') {
      return ['range' => [strtoupper($condition->getField()) => ['gte' => $condition->getValue()]]];
    }
    elseif ($condition->getOperator() == '<') {
      return ['range' => [strtoupper($condition->getField()) => ['lt' => $condition->getValue()]]];
    }
    elseif ($condition->getOperator() == '<=') {
      return ['range' => [strtoupper($condition->getField()) => ['lte' => $condition->getValue()]]];
    }
    else {
      return ['term' => [strtoupper($condition->getField()) => $condition->getValue()]];
    }
  }

  /**
   * Handle facets.
   *
   * @param array $available_facets
   *   The configured facets for the index.
   * @param string $text
   *   Fulltext keys to search.
   * @param array $terms_expression
   *   Query conditions.
   *
   * @return array
   *   Facets keyed by facet_id.
   */
  protected function getFacets(array $available_facets = [], string $text = NULL, array $terms_expression = []) {
    $facets = $response_facets = [];
    $europa_response = $this->getClient()->getFacets($text, NULL, NULL, $terms_expression);

    // Prepare response facets.
    foreach ($europa_response->getFacets() as $facet) {
      $facet_name = strtolower($facet->getRawName());
      $response_facets[$facet_name] = $facet;
    }

    // Loop through available facets to build the ones with results.
    foreach ($available_facets as $available_facet) {
      $facet_name = $available_facet['field'];
      if (!empty($response_facets[$facet_name])) {
        $response_facet = $response_facets[$facet_name];
        $facet_results = [];
        foreach ($response_facet->getValues() as $value) {
          $facet_results[] = [
            'filter' => $value->getRawValue(),
            'count' => $value->getCount(),
          ];
        }

        $facets[$facet_name] = $facet_results;
      }
    }

    return $facets;
  }

  /**
   * Returns an entity from mapped values.
   *
   * @param array $metadata
   *   The metadata array.
   * @param array $index_fields
   *   The index fields.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The mapped entity.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  protected function getMappedEntity(array $metadata, array $index_fields) : ContentEntityInterface {
    foreach ($index_fields as $field) {
      $metadata_key = strtoupper($field->getFieldIdentifier());

      // Non supported types.
      // @todo Better support all field types.
      if (!in_array($field->getType(), [
        'integer',
        'string',
        'text',
        'fulltext',
        'date',
      ])) {
        continue;
      }

      if (!($field->getDataDefinition() instanceof FieldItemDataDefinition)) {
        continue;
      }

      // @todo Better support original fields.
      $entity_reference_types = [
        'entity_reference',
        'entity_reference_revisions',
      ];
      if ($metadata_key != 'TYPE' && in_array($field->getDataDefinition()->getFieldDefinition()->getType(), $entity_reference_types)) {
        continue;
      }

      if (empty($metadata[$metadata_key][0])) {
        continue;
      }

      // Dates need to be converted.
      // @todo Proper support for date fields and types.
      if ($field->getType() == 'date') {
        $time = strtotime($metadata[$metadata_key][0]);

        if ($field->getDataDefinition()->getFieldDefinition()->getType() == 'daterange_timezone') {
          $values[$field->getOriginalFieldIdentifier()] = [
            'value' => date('Y-m-d\TH:i:s', $time),
            'end_value' => date('Y-m-d\TH:i:s', $time),
            'timezone' => 'Europe/Brussels',
          ];
        }
        elseif ($field->getDataDefinition()->getFieldDefinition()->getType() == 'datetime') {
          $values[$field->getOriginalFieldIdentifier()] = date('Y-m-d', $time);
        }
        else {
          $values[$field->getOriginalFieldIdentifier()] = $time;
        }
      }
      else {
        $values[$field->getOriginalFieldIdentifier()] = $metadata[$metadata_key][0];
      }

    }
    // @todo This is only needed because of link lists as DefaultEntityValueResolverSubscriber would fail otherwise.
    $values['nid'] = 9999999999;

    // @todo Change to generic entities.
    $entity = Node::create($values);

    // @todo Find a better way to force redirect.
    $entity->europa_list_redirect_link = $metadata['esST_URL'][0];
    return $entity;
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
      // The client uses PSR standards.
      if (!$this->httpClient instanceof PsrClient) {
        $this->httpClient = new GuzzleAdapter($this->httpClient);
      }
      // @todo Refactor this instantiation to a new plugin type in OEL-152.
      // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/OEL-152
      // @todo Replace \Http\Factory\Guzzle with factories provided by
      //   laminas/laminas-diactoros:^2 once support for Drupal 8.9 is dropped.
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
      $this->eventService->dispatch(DocumentCreationEvent::class, $event);

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
