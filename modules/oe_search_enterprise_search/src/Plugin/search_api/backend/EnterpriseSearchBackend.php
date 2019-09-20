<?php

declare(strict_types = 1);

namespace Drupal\oe_search_enterprise_search\Plugin\search_api\backend;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\QueryInterface;
use Http\Adapter\Guzzle6\Client as HttpClient;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use OpenEuropa\EuropaSearchClient\Api\IngestionApi;
use OpenEuropa\EuropaSearchClient\Api\SearchApi;
use OpenEuropa\EuropaSearchClient\Client;
use OpenEuropa\EuropaSearchClient\ClientInterface;
use OpenEuropa\EuropaSearchClient\Model\Document;

/**
 * European Commission Enterprise Search backend for search_api.
 *
 * @SearchApiBackend(
 *   id = "oe_search_enterprise_search",
 *   label = @Translation("EC Enterprise Search"),
 *   description = @Translation("Index items using EC Enterprise Search backend.")
 * )
 */
class EnterpriseSearchBackend extends BackendPluginBase implements PluginFormInterface {

  use PluginFormTrait {
    submitConfigurationForm as traitSubmitConfigurationForm;
  }
  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => NULL,
      'database' => NULL,
      'ingestion_api_endpoint' => NULL,
      'search_api_endpoint' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The api key is a unique key generated by the search team. It ties your application to a specific behaviour (allowed field names, security details, display templates, field translations, etc).'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['api_key'],
    ];

    $form['database'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Database'),
      '#description' => $this->t('The database element correspond to a dataSource that contains the documents.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['database'],
    ];

    $form['ingestion_api_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Ingestion API endpoint'),
      '#description' => $this->t('The URL of the endpoint where the Ingestion API is available.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['ingestion_api_endpoint'],
    ];

    $form['search_api_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Search API endpoint'),
      '#description' => $this->t('The URL of the endpoint where the Search API is available.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['search_api_endpoint'],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    $client = $this->getClient();
    $api = new IngestionApi($client);

    // @todo Support multiple indexes by generating a reference id that takes
    //   into account index id and item id. Store the item id as separate
    //   field.
    $indexed = [];
    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    foreach ($items as $id => $item) {
      try {
        $ingestion = $api->ingestText([
          'uri' => $item->getOriginalObject()->getValue()->toUrl()->setAbsolute()->toString(),
          'text' => $item->getOriginalObject()->getValue()->label(),
          'reference' => $id,
        ]);
        $indexed[] = $ingestion->getReference();
      }
      catch (\Exception $e) {
        $this->getLogger()->warning($e->getMessage());
      }
    }

    return $indexed;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteItems(IndexInterface $index, array $item_ids) {
    $client = $this->getClient();
    $api = new IngestionApi($client);
    foreach ($item_ids as $item_id) {
      try {
        $api->deleteDocument($item_id);
      }
      catch (\Exception $e) {
        $this->getLogger()->warning($e->getMessage());
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL) {
    // There is no method to bulk delete items in the Enterprise Search API.
    // Fetch all the documents available and then delete them one by one.
    $client = $this->getClient();
    $api = new SearchApi($client);
    $search = $api->search();

    $item_ids = array_map(function (Document $document) {
      return $document->getReference();
    }, $search->getResults());

    // @todo Handle datasource.
    $this->deleteItems($index, $item_ids);
  }

  /**
   * {@inheritDoc}
   */
  public function search(QueryInterface $query) {
    // @todo Make sure the search is run using the proper index.
    $client = $this->getClient();
    $api = new SearchApi($client);
    $search = $api->search();

    $result_set = $query->getResults();
    $result_set->setResultCount($search->getTotalResults());

    foreach ($search->getResults() as $document) {
      $result_item = $this->fieldsHelper->createItem($query->getIndex(), $document->getReference());
      $result_set->addResultItem($result_item);
    }

    \Drupal::messenger()->addWarning($this->t('Search is not fully supported yet in %backend backends.', [
      '%backend' => $this->label(),
    ]));
  }

  /**
   * Returns a client instance.
   *
   * @return \OpenEuropa\EuropaSearchClient\ClientInterface
   *   The client.
   */
  protected function getClient(): ClientInterface {
    $configuration = $this->configuration;
    // Normalise configuration name from Drupal standards.
    $configuration['apiKey'] = $configuration['api_key'];
    unset($configuration['api_key']);

    // @todo Make the client available through a service.
    $guzzle_psr = new HttpClient(\Drupal::service('http_client'));
    $client = new Client($guzzle_psr, new RequestFactory(), new StreamFactory(), $configuration);

    return $client;
  }

}
