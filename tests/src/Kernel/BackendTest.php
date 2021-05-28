<?php

declare(strict_types=1);

namespace Drupal\oe_search\Tests;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;

/**
 * Class for testing Europa Search drupal integration.
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
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'user',
    'system',
    'entity_test',
    'search_api',
    'oe_search',
    'oe_search_test',
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

    entity_test_create_bundle('item', NULL, 'entity_test_mulrev_changed');
  }

  /**
   * Test Ingestion.
   */
  public function testIndexItems(): void {
    $backend = Server::load($this->serverId)->getBackend();
    $index = Index::load($this->indexId);
    $items = [];
    $backend->indexItems($index, $items);
    $this->assertTrue(TRUE);
  }

}
