<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Tests;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\oe_search\Utility;
use Drupal\search_api\Utility\Utility as SearchApiUtility;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use OpenEuropa\Tests\EuropaSearchClient\Traits\InspectTestRequestTrait;
use Psr\Http\Message\RequestInterface;

/**
 * Tests Europa Search Drupal Search API integration.
 *
 * @group oe_search
 */
class BackendTest extends KernelTestBase {

  use ExampleContentTrait;
  use InspectTestRequestTrait;

  /**
   * A Search API server ID.
   *
   * @var string
   */
  protected $serverId = 'europa_search_server';

  /**
   * A Search API index ID.
   *
   * @var string
   */
  protected $indexId = 'europa_search_index';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'http_request_mock',
    'oe_search',
    'oe_search_test',
    'search_api',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig([
      'search_api',
      'oe_search',
      'oe_search_test',
    ]);

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!SearchApiUtility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    $this->setUpExampleStructure();
    $this->insertExampleContent();

    $settings = [
      'oe_search' => [
        'server' => [
          'europa_search_server' => [
            'consumer_key' => 'foo',
            'consumer_secret' => 'bar',
          ],
        ],
      ],
    ] + Settings::getAll();
    new Settings($settings);
  }

  /**
   * Test Ingestion.
   */
  public function testIndexItems(): void {
    $field_helper = $this->container->get('search_api.fields_helper');
    $datasource_manager = $this->container->get('plugin.manager.search_api.datasource');
    /** @var \Drupal\oe_search\Plugin\search_api\backend\SearchApiEuropaSearchBackend $backend */
    $backend = Server::load($this->serverId)->getBackend();
    $index = Index::load($this->indexId);

    /** @var \Drupal\search_api\Datasource\DatasourceInterface $datasource */
    $datasource = $datasource_manager->createInstance('entity:entity_test_mulrev_changed');
    $datasource->setIndex($index);

    $item_ids = array_map(function (EntityTestMulRevChanged $entity) use ($datasource): string {
      return SearchApiUtility::createCombinedId($datasource->getPluginId(), "{$entity->id()}:{$entity->language()->getId()}");
    }, $this->entities);

    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    $items = [];
    foreach ($item_ids as $item_id) {
      $items[$item_id] = $field_helper->createItem($index, $item_id, $datasource);
    }

    // The 'entity_test_mulrev_changed' entity type is not implementing the
    // \Drupal\Core\Entity\EntityPublishedInterface interface, thus it cannot be
    // indexed by default.
    // @see \Drupal\oe_search\Plugin\search_api\backend\SearchApiEuropaSearchBackend::getDocuments()
    $backend->indexItems($index, $items);
    $this->assertServiceMockCalls('/ingest/text', 0, 0);

    // Enable ingestion of 'entity_test_mulrev_changed' entities.
    // @see \Drupal\oe_search_test\EventSubscriber\OeSearchTestSubscriber::indexEntityTestMulRevChanged()
    $this->container->get('state')->set('oe_search_test.enable_document_alter', TRUE);
    $backend->indexItems($index, $items);
    $this->assertServiceMockCalls('/ingest/text', 5, 5);

    // Compare set data with received data.
    $requests = $this->getServiceMockRequests('/ingest/text');
    $this->assertIngestedItem($requests[0], $items, $item_ids[1], 1);
    $this->assertIngestedItem($requests[1], $items, $item_ids[2], 2);
    $this->assertIngestedItem($requests[2], $items, $item_ids[3], 3);
    $this->assertIngestedItem($requests[3], $items, $item_ids[4], 4);
    // @todo check that items where added to search_api_items table.
  }

  /**
   * Assert data for one ingested item.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   * @param array $items
   *   The items sent to ingestion.
   * @param string $item_id
   *   The search_api_id of the current item.
   * @param int $id
   *   The id of the current item.
   */
  protected function assertIngestedItem(RequestInterface $request, array $items, string $item_id, int $id): void {
    $item = $items[$item_id];
    $entity = $item->getOriginalObject()->getValue();
    // Assert query parameters.
    parse_str($request->getUri()->getQuery(), $parameters);
    $this->assertSame($entity->toUrl()->setAbsolute()->toString(), $parameters['uri']);
    $this->assertSame(Utility::getSiteHash() . '-europa_search_index-' . $item_id, $parameters['reference']);
    $this->assertSame('["en"]', $parameters['language']);
    // Assert request body.
    $this->inspectBoundary($request);
    $parts = $this->getMultiParts($request);
    $expected_meta = json_encode([
      'search_api_id' => [$item_id],
      'search_api_datasource' => ['entity:entity_test_mulrev_changed'],
      'search_api_language' => ['en'],
      'search_api_site_hash' => [Utility::getSiteHash()],
      'search_api_index_id' => ['europa_search_index'],
      'id' => [$id],
      'name' => [$entity->label()],
      'created' => [$item->getField('created')->getValues()['0'] * 1000],
    ]);

    $this->inspectPart($parts[0], 'application/json', 'metadata', strlen($expected_meta), $expected_meta);
    $this->inspectPart($parts[1], 'text/plain', 'text', strlen($entity->label()), $entity->label());
  }

  /**
   * Asserts that the service mock methods are called.
   *
   * @param string $path
   *   The request path.
   * @param int $applies_calls
   *   Received requests count.
   * @param int $get_response_calls
   *   Count of replies from mocked server.
   *
   * @throws \Exception
   */
  protected function assertServiceMockCalls(string $path, int $applies_calls, int $get_response_calls): void {
    $state = $this->container->get('state');
    $calls = $state->get('oe_search_test.service_mock_calls', []);

    if (!isset($calls[$path])) {
      $calls[$path] = [
        'applies' => 0,
        'getResponse' => 0,
      ];
    }

    $this->assertSame($applies_calls, $calls[$path]['applies']);
    $this->assertSame($get_response_calls, $calls[$path]['getResponse']);

    // Leave the place clean for future assertions.
    $state->delete('oe_search_test.service_mock_calls');
  }

  /**
   * Gets the received request by the mock server.
   *
   * @param string $path
   *   Path to filter list by.
   *
   * @return array
   *   List or requests.
   */
  protected function getServiceMockRequests(string $path): array {
    $state = $this->container->get('state');
    $requests = $state->get('oe_search_test.service_mock_requests', []);

    return $requests[$path];
  }

}
