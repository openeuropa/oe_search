<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_search\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\oe_search_mock\EuropaSearchMockRequestCollector;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\oe_search\Utility;
use Drupal\oe_search_mock\Config\EuropaSearchMockServerConfigOverrider;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility\Utility as SearchApiUtility;
use OpenEuropa\Tests\EuropaSearchClient\Traits\AssertTestRequestTrait;
use Psr\Http\Message\RequestInterface;

/**
 * Base class for testing Europa Search ingestion.
 */
abstract class BackendIngestionTestBase extends KernelTestBase {

  use AssertTestRequestTrait;
  use ExampleContentTrait;
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * A list of item IDs.
   *
   * @var array
   */
  protected $itemIds = [];

  /**
   * A Search API index ID.
   *
   * @var string
   */
  protected $indexId = 'europa_search_index';

  /**
   * The search api server.
   *
   * @var \Drupal\search_api\Entity\Server
   */
  protected $server;


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
   * Media entities.
   *
   * @var array
   */
  protected $mediaEntities = [];

  /**
   * Media item IDs.
   *
   * @var string[]
   */
  protected $mediaItemIds;

  /**
   * Media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

  /**
   * The search api task manager.
   *
   * @var \Drupal\search_api\Task\TaskManager
   */
  protected $taskManager;

  /**
   * The datasource attached to the media index.
   *
   * @var \Drupal\search_api\Datasource\DatasourceInterface
   */
  protected $datasourceMedia;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'http_request_mock',
    'oe_search',
    'oe_search_test',
    'oe_search_mock',
    'search_api',
    'system',
    'user',
    'media',
    'image',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');
    $this->installEntitySchema('search_api_task');
    $this->installConfig([
      'media',
      'image',
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

    // Set constant site_hash value for test purpose aligned to mocked requests.
    \Drupal::state()->set('oe_search.site_hash', 'xxxxx');

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
    $this->taskManager = $this->container->get('search_api.task_manager');

    $this->server = Server::load('europa_search_server');
    $this->backend = $this->server->getBackend();
    $this->index = Index::load($this->indexId);
    $this->datasource = $datasource_manager->createInstance('entity:entity_test_mulrev_changed');
    $this->datasource->setIndex($this->index);

    $this->itemIds = array_map(function (EntityTestMulRevChanged $entity): string {
      return SearchApiUtility::createCombinedId($this->datasource->getPluginId(), "{$entity->id()}:{$entity->language()->getId()}");
    }, $this->entities);

    // Create test medias.
    $this->mediaType = $this->createMediaType('file');
    for ($i = 1; $i <= 5; $i++) {
      $file = File::create([
        'uri' => $this->getTestFiles('image')[0]->uri,
      ]);
      $file->setPermanent();
      $file->save();

      $media = Media::create([
        'name' => 'Test file media ' . $i,
        'bundle' => $this->mediaType->id(),
        'field_media_file' => $file->id(),
      ]);
      $media->save();
      $this->mediaEntities[$media->id()] = $media;
    }

    $this->datasourceMedia = $datasource_manager->createInstance('entity:media');
    $this->datasourceMedia->setIndex($this->index);

    $this->mediaItemIds = array_map(function (MediaInterface $entity): string {
      return SearchApiUtility::createCombinedId($this->datasourceMedia->getPluginId(), "{$entity->id()}:{$entity->language()->getId()}");
    }, $this->mediaEntities);
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
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_TEXT, 0, 0);

    // Enable ingestion of 'entity_test_mulrev_changed' entities.
    // @see \Drupal\oe_search_mock\EventSubscriber\OeSearchTestSubscriber::indexEntityTestMulRevChanged()
    $this->container->get('state')->set('oe_search_test.enable_document_alter', TRUE);
    $this->backend->indexItems($this->index, $items);
    // Only one token request as it gets cached after first request.
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_TOKEN, 1, 1, FALSE);
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_TEXT, 5, 5);

    // Check that the data sent is correct.
    $requests = EuropaSearchMockRequestCollector::getCollectedRequests(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_TEXT);
    $this->assertCount(5, $requests);
    $this->assertIngestedItem($requests[0], $items, 1);
    $this->assertIngestedItem($requests[1], $items, 2);
    $this->assertIngestedItem($requests[2], $items, 3);
    $this->assertIngestedItem($requests[3], $items, 4);
    $this->assertIngestedItem($requests[4], $items, 5);
  }

  /**
   * File ingestion.
   *
   * @covers ::indexItems
   */
  public function testMediaIndexItems(): void {
    $field_helper = $this->container->get('search_api.fields_helper');

    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    $items = [];
    foreach ($this->mediaItemIds as $item_id) {
      $items[$item_id] = $field_helper->createItem($this->index, $item_id, $this->datasourceMedia);
    }

    $this->backend->indexItems($this->index, $items);
    // Only one token request as it gets cached after first request.
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_TOKEN, 1, 1, FALSE);
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_FILE, 5, 5);

    // Compare the sent files with received data.
    $requests = EuropaSearchMockRequestCollector::getCollectedRequests(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_FILE);
    $this->assertCount(5, $requests);
    $this->assertIngestedFileItem($requests[0], $items, 1);
    $this->assertIngestedFileItem($requests[1], $items, 2);
    $this->assertIngestedFileItem($requests[2], $items, 3);
    $this->assertIngestedFileItem($requests[3], $items, 4);
    $this->assertIngestedFileItem($requests[4], $items, 5);
  }

  /**
   * Assert data for an ingested file item.
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
    $expected_meta = [
      'SEARCH_API_ID' => [$item_id],
      'SEARCH_API_DATASOURCE' => ['entity:entity_test_mulrev_changed'],
      'SEARCH_API_LANGUAGE' => ['en'],
      'SEARCH_API_SITE_HASH' => [Utility::getSiteHash()],
      'SEARCH_API_INDEX_ID' => [$this->indexId],
      'id' => [$id],
      'name' => [$entity->label()],
      'created' => [$item->getField('created')->getValues()['0'] * 1000],
      'type' => [$entity->bundle()],
    ];

    $this->assertIngestedItemMetadata($parts[0], $expected_meta);
    $this->assertMultipartStreamResource($parts[1], 'text/plain', 'text', strlen($entity->label()), $entity->label());
  }

  /**
   * Assert data for the ingested file item.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   * @param array $items
   *   The items sent to ingestion.
   * @param int $id
   *   The id of the current item.
   */
  protected function assertIngestedFileItem(RequestInterface $request, array $items, int $id): void {
    $item_id = $this->mediaItemIds[$id];
    $entity = $this->mediaEntities[$id];
    // Assert query parameters.
    parse_str($request->getUri()->getQuery(), $parameters);
    $fid = $entity->getSource()->getSourceFieldValue($entity);
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
    $this->assertStringContainsString($file->createFileUrl(), $parameters['uri']);
    $this->assertSame(Utility::getSiteHash() . '-' . $this->indexId . '-' . $item_id, $parameters['reference']);
    $this->assertSame('["en"]', $parameters['language']);
    // Assert request body.
    $boundary = $this->getRequestBoundary($request);
    $this->assertBoundary($request, $boundary);
    $parts = $this->getRequestMultipartStreamResources($request, $boundary);
    $expected_meta = [
      'SEARCH_API_ID' => [$item_id],
      'SEARCH_API_DATASOURCE' => ['entity:media'],
      'SEARCH_API_LANGUAGE' => ['en'],
      'SEARCH_API_SITE_HASH' => [Utility::getSiteHash()],
      'SEARCH_API_INDEX_ID' => [$this->indexId],
    ];

    $this->assertIngestedItemMetadata($parts[0], $expected_meta);
  }

  /**
   * Asserts the JSON metadata part of an ingestion request.
   *
   * The order of the metadata fields depends on the order of the index field
   * settings, which varies across Drupal core and Search API versions, so the
   * decoded metadata is compared instead of the raw JSON string.
   *
   * @param string $part
   *   The multipart stream resource.
   * @param array $expected
   *   The expected metadata values, keyed by field name.
   */
  protected function assertIngestedItemMetadata(string $part, array $expected): void {
    [$headers, $content] = explode("\r\n\r\n", $part);
    $headers = explode("\r\n", $headers);
    $this->assertContains('Content-Type: application/json', $headers);
    $this->assertContains('Content-Disposition: form-data; name="metadata"; filename="metadata"', $headers);
    $this->assertEquals($expected, json_decode($content, TRUE));
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
   * @param bool $clean
   *   Whether the requests should be clean for future assertions.
   *
   * @throws \Exception
   */
  protected function assertServiceMockCalls(string $path, int $applies_calls, int $get_response_calls, bool $clean = TRUE): void {
    $state = $this->container->get('state');
    $calls = $state->get('oe_search_mock.service_mock_calls', []);

    if (!isset($calls[$path])) {
      $calls[$path] = [
        'applies' => 0,
        'getResponse' => 0,
      ];
    }

    $this->assertSame($applies_calls, $calls[$path]['applies']);
    $this->assertSame($get_response_calls, $calls[$path]['getResponse']);

    // Leave the place clean for future assertions.
    if ($clean) {
      $state->delete('oe_search_mock.service_mock_calls');
    }
  }

}
