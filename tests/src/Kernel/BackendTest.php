<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Tests;

use Drupal\Core\Site\Settings;
use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;

/**
 * Tests Europa Search Drupal Search API integration.
 *
 * @group oe_search
 */
class BackendTest extends KernelTestBase {

  use ExampleContentTrait;

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
    if (!Utility::isRunningInCli()) {
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
    $backend = Server::load($this->serverId)->getBackend();
    $index = Index::load($this->indexId);

    /** @var \Drupal\search_api\Datasource\DatasourceInterface $datasource */
    $datasource = $datasource_manager->createInstance('entity:entity_test_mulrev_changed');
    $datasource->setIndex($index);

    $item_ids = array_map(function (EntityTestMulRevChanged $entity) use ($datasource): string {
      return Utility::createCombinedId($datasource->getPluginId(), "{$entity->id()}:{$entity->language()->getId()}");
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
    $this->assertServiceMockCalls(0, 0);

    // Enable ingestion of 'entity_test_mulrev_changed' entities.
    // @see \Drupal\oe_search_test\EventSubscriber\OeSearchTestSubscriber::indexEntityTestMulRevChanged()
    $this->container->get('state')->set('oe_search_test.enable_document_alter', TRUE);
    $backend->indexItems($index, $items);
    $this->assertServiceMockCalls(1, 1);

    // print_r($items['entity:entity_test_mulrev_changed/1:en']->getOriginalObject()->toArray());
  }

  /**
   * Asserts that the service mock methods are called.
   */
  protected function assertServiceMockCalls(int $applies_calls, int $get_response_calls): void {
    $state = $this->container->get('state');
    $calls = $state->get('oe_search_test.service_mock_calls', [
      'applies' => 0,
      'getResponse' => 0,
    ]);

    $this->assertSame($applies_calls, $calls['applies']);
    $this->assertSame($get_response_calls, $calls['getResponse']);

    // Leave the place clean for future assertions.
    $state->delete('oe_search_test.service_mock_calls');
  }

}
