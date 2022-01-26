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
use OpenEuropa\Tests\EuropaSearchClient\Traits\AssertTestRequestTrait;
use Psr\Http\Message\RequestInterface;

/**
 * Tests Europa Search Drupal Search API integration.
 *
 * @coversDefaultClass \Drupal\oe_search\Plugin\search_api\backend\SearchApiEuropaSearchBackend
 * @group oe_search
 */
class BackendTest extends KernelTestBase {

  use ExampleContentTrait;
  use AssertTestRequestTrait;

  /**
   * A Search API index ID.
   *
   * @var string
   */
  protected $indexId = 'europa_search_index';

  /**
   * The Search API Europa Search backend.
   *
   * @var \Drupal\search_api\Backend\BackendInterface
   */
  protected $backend;

  /**
   * The datasource attached to the index.
   *
   * @var \Drupal\search_api\Datasource\DatasourceInterface
   */
  protected $datasource;

  /**
   * A Search API index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * A list of item IDs.
   *
   * @var array
   */
  protected $itemIds = [];

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

    $datasource_manager = $this->container->get('plugin.manager.search_api.datasource');
    $this->backend = Server::load('europa_search_server')->getBackend();
    $this->index = Index::load($this->indexId);
    $this->datasource = $datasource_manager->createInstance('entity:entity_test_mulrev_changed');
    $this->datasource->setIndex($this->index);

    $this->itemIds = array_map(function (EntityTestMulRevChanged $entity): string {
      return SearchApiUtility::createCombinedId($this->datasource->getPluginId(), "{$entity->id()}:{$entity->language()->getId()}");
    }, $this->entities);
  }

  /**
   * Test Ingestion.
   *
   * @covers ::indexItems
   */
  public function testIndexItems(): void {
    $field_helper = $this->container->get('search_api.fields_helper');

    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    $items = [];
    foreach ($this->itemIds as $item_id) {
      $items[$item_id] = $field_helper->createItem($this->index, $item_id, $this->datasource);
    }

    // The 'entity_test_mulrev_changed' entity type is not implementing the
    // \Drupal\Core\Entity\EntityPublishedInterface interface, thus it cannot be
    // indexed by default.
    // @see \Drupal\oe_search\Plugin\search_api\backend\SearchApiEuropaSearchBackend::getDocuments()
    $this->backend->indexItems($this->index, $items);
    $this->assertServiceMockCalls('/ingest/text', 0, 0);

    // Enable ingestion of 'entity_test_mulrev_changed' entities.
    // @see \Drupal\oe_search_test\EventSubscriber\OeSearchTestSubscriber::indexEntityTestMulRevChanged()
    $this->container->get('state')->set('oe_search_test.enable_document_alter', TRUE);
    $this->backend->indexItems($this->index, $items);
    $this->assertServiceMockCalls('/ingest/text', 5, 5);

    // Compare sent data with received data.
    $requests = $this->getServiceMockRequests('/ingest/text');
    $this->assertCount(5, $requests);
    $this->assertIngestedItem($requests[0], $items, 1);
    $this->assertIngestedItem($requests[1], $items, 2);
    $this->assertIngestedItem($requests[2], $items, 3);
    $this->assertIngestedItem($requests[3], $items, 4);
    $this->assertIngestedItem($requests[4], $items, 5);
  }

  /**
   * @covers ::deleteItems
   */
  public function testDeleteItems(): void {
    $this->assertServiceMockCalls('/ingest/delete', 0, 0);
    $this->backend->deleteItems($this->index, $this->itemIds);
    $this->assertServiceMockCalls('/ingest/delete', 5, 5);
    // Compare sent data with received data.
    $requests = $this->getServiceMockRequests('/ingest/delete');
    $this->assertCount(5, $requests);
    $this->assertDeletedItem($requests[0], 1);
    $this->assertDeletedItem($requests[1], 2);
    $this->assertDeletedItem($requests[2], 3);
    $this->assertDeletedItem($requests[3], 4);
    $this->assertDeletedItem($requests[4], 5);
  }

  /**
   * @covers ::deleteAllIndexItems
   */
  public function testDeleteAllIndexItems(): void {
    $this->assertServiceMockCalls('/ingest/delete', 0, 0);
    $this->backend->deleteAllIndexItems($this->index);
    $this->assertServiceMockCalls('/ingest/delete', 5, 5);
    // Compare sent data with received data.
    $requests = $this->getServiceMockRequests('/ingest/delete');
    $this->assertCount(5, $requests);
    $this->assertDeletedItem($requests[0], 1);
    $this->assertDeletedItem($requests[1], 2);
    $this->assertDeletedItem($requests[2], 3);
    $this->assertDeletedItem($requests[3], 4);
    $this->assertDeletedItem($requests[4], 5);
  }

  /**
   * Assert data for one ingested item.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   * @param array $items
   *   The items sent to ingestion.
   * @param int $id
   *   The id of the current item.
   */
  protected function assertIngestedItem(RequestInterface $request, array $items, int $id): void {
    $item_id = $this->itemIds[$id];
    $item = $items[$item_id];
    $entity = $this->entities[$id];
    // Assert query parameters.
    parse_str($request->getUri()->getQuery(), $parameters);
    $this->assertSame($entity->toUrl()->setAbsolute()->toString(), $parameters['uri']);
    $this->assertSame(Utility::getSiteHash() . '-' . $this->indexId . '-' . $item_id, $parameters['reference']);
    $this->assertSame('["en"]', $parameters['language']);
    // Assert request body.
    $boundary = $this->getRequestBoundary($request);
    $this->assertBoundary($request, $boundary);
    $parts = $this->getRequestMultipartStreamResources($request, $boundary);
    $expected_meta = json_encode([
      'SEARCH_API_ID' => [$item_id],
      'SEARCH_API_DATASOURCE' => ['entity:entity_test_mulrev_changed'],
      'SEARCH_API_LANGUAGE' => ['en'],
      'SEARCH_API_SITE_HASH' => [Utility::getSiteHash()],
      'SEARCH_API_INDEX_ID' => [$this->indexId],
      'id' => [$id],
      'name' => [$entity->label()],
      'created' => [$item->getField('created')->getValues()['0'] * 1000],
    ]);

    $this->assertMultipartStreamResource($parts[0], 'application/json', 'metadata', strlen($expected_meta), $expected_meta);
    $this->assertMultipartStreamResource($parts[1], 'text/plain', 'text', strlen($entity->label()), $entity->label());
  }

  /**
   * Assert data for one deleted item.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   * @param int $id
   *   The id of the current item.
   */
  protected function assertDeletedItem(RequestInterface $request, int $id): void {
    $item_id = $this->itemIds[$id];
    parse_str($request->getUri()->getQuery(), $parameters);
    $this->assertSame(Utility::createReference($this->indexId, $item_id), $parameters['reference']);
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
