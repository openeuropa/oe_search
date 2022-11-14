<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\search_api\Utility\Utility as SearchApiUtility;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\views\Views;
use OpenEuropa\Tests\EuropaSearchClient\Traits\AssertTestRequestTrait;

/**
 * Tests Europa Search Drupal Search API integration.
 *
 * @coversDefaultClass \Drupal\oe_search\Plugin\search_api\backend\SearchApiEuropaSearchBackend
 * @group oe_search
 */
class BackendSearchTest extends KernelTestBase {

  use ExampleContentTrait;
  use AssertTestRequestTrait;
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

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
    'oe_search_mock',
    'search_api',
    'system',
    'datetime',
    'user',
    'media',
    'text',
    'image',
    'file',
    'views',
  ];

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
      'user',
      'views',
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

    $this->backend = Server::load('europa_search_server')->getBackend();
    $this->index = Index::load($this->indexId);
    entity_test_create_bundle('item', NULL, 'entity_test_mulrev_changed');
    entity_test_create_bundle('article', NULL, 'entity_test_mulrev_changed');

    // Body.
    FieldStorageConfig::create([
      'field_name' => 'body',
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'field_name' => 'body',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'item',
    ])->save();
    FieldConfig::create([
      'field_name' => 'body',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'article',
    ])->save();

    // Keywords.
    FieldStorageConfig::create([
      'field_name' => 'keywords',
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'field_name' => 'keywords',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'item',
    ])->save();
    FieldConfig::create([
      'field_name' => 'keywords',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'article',
    ])->save();

    // Boolean.
    FieldStorageConfig::create([
      'field_name' => 'highlighted',
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'boolean',
    ])->save();
    FieldConfig::create([
      'field_name' => 'highlighted',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'item',
    ])->save();
    FieldConfig::create([
      'field_name' => 'highlighted',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'article',
    ])->save();

    // Date.
    FieldStorageConfig::create([
      'field_name' => 'publication_date',
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'datetime',
    ])->save();
    FieldConfig::create([
      'field_name' => 'publication_date',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'item',
    ])->save();
    FieldConfig::create([
      'field_name' => 'publication_date',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'article',
    ])->save();

    // Datetime.
    FieldStorageConfig::create([
      'field_name' => 'cron_time',
      'entity_type' => 'entity_test_mulrev_changed',
      'type' => 'datetime',
    ])->save();
    FieldConfig::create([
      'field_name' => 'cron_time',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'item',
    ])->save();
    FieldConfig::create([
      'field_name' => 'cron_time',
      'entity_type' => 'entity_test_mulrev_changed',
      'bundle' => 'article',
    ])->save();
  }

  /**
   * Creates test content.
   */
  protected function createTestContent(): void {
    // Create 10 items.
    for ($i = 1; $i <= 10; $i++) {
      $this->addTestEntity($i, [
        'name' => 'item ' . $i,
        'body' => 'bar test casE',
        'type' => 'item',
        'category' => 'item_category',
      ]);
    }

    // Create 5 articles.
    for ($i = 1; $i <= 5; $i++) {
      $this->addTestEntity($i + 10, [
        'name' => 'article ' . $i,
        'body' => 'bar test casE',
        'type' => 'article',
        'keywords' => ['orange', 'apple', 'grape'],
        'category' => 'item_category',
      ]);
    }
  }

  /**
   * Tests search with entity load set to only local entities.
   *
   * @covers ::search
   */
  public function testLocalSearch(): void {
    $this->index->setThirdPartySetting('oe_search', 'europa_search_entity_mode', 'local');
    $this->index->save();
    $this->createTestContent();

    // Search all local results.
    $query = $this->index->query();
    $response = $query->execute();
    $result_items = $response->getResultItems();
    $this->assertCount(10, $result_items);
    $this->assertEquals(15, $response->getResultCount());
    $first_result = reset($result_items);
    $this->assertEquals('entity:entity_test_mulrev_changed/1:en', $first_result->getId());

    // Paginate.
    $query = $this->index->query(['offset' => 10, 'limit' => 10]);
    $response = $query->execute();
    $result_items = $response->getResultItems();
    $this->assertCount(5, $result_items);
    $this->assertEquals(15, $response->getResultCount());
    $first_result = reset($result_items);
    $this->assertEquals('entity:entity_test_mulrev_changed/11:en', $first_result->getId());

    // Search by keys.
    $query = $this->index->query();
    $query->keys(['barista']);
    $response = $query->execute();
    $result_items = $response->getResultItems();
    $this->assertCount(2, $result_items);
    $this->assertEquals(2, $response->getResultCount());
    $first_result = reset($result_items);
    $this->assertEquals('entity:entity_test_mulrev_changed/2:en', $first_result->getId());

    // Search with a filter.
    $query = $this->index->query();
    $query->addCondition('TYPE', 'item');
    $response = $query->execute();
    $result_items = $response->getResultItems();
    $this->assertCount(10, $result_items);
    $this->assertEquals(12, $response->getResultCount());
    $first_result = reset($result_items);
    $this->assertEquals('entity:entity_test_mulrev_changed/1:en', $first_result->getId());
  }

  /**
   * Tests search with entity load set to remote entities.
   *
   * @covers ::search
   */
  public function testRemoteSearch(): void {
    $this->index->setThirdPartySetting('oe_search', 'europa_search_entity_mode', 'remote');
    $this->index->save();
    $this->createTestContent();

    // Search all item results.
    $query = $this->index->query();
    $query->addCondition('TYPE', 'item');
    $response = $query->execute();
    $result_items = $response->getResultItems();
    $this->assertCount(10, $result_items);
    $this->assertEquals(12, $response->getResultCount());

    // First item.
    $first_result = array_shift($result_items);
    $first_entity = $first_result->getOriginalObject()->getEntity();
    $this->assertEquals('item 1', $first_entity->label());
    $this->assertEquals('bar test casE', $first_entity->body->value);
    $this->assertEquals([
      ['value' => 'orange'],
      ['value' => 'apple'],
      ['value' => 'grape'],
    ],
      $first_entity->keywords->getValue()
    );
    $this->assertEquals('item', $first_entity->bundle());
    $this->assertEmpty($first_entity->highlighted);

    // Now second page.
    $query = $this->index->query();
    $query->addCondition('TYPE', 'item');
    $query->setOption('offset', 10);
    $query->setOption('limit', 10);
    $response = $query->execute();

    // Second page only contains remote entities.
    $result_items = $response->getResultItems();
    $this->assertCount(2, $result_items);
    $this->assertEquals(12, $response->getResultCount());

    $first_result = $result_items['https://demo.ec.europa.eu/entity-17'];
    $first_entity = $first_result->getOriginalObject()->getEntity();
    $this->assertEquals('remote item', $first_entity->label());
    $this->assertEquals('bar iteM', $first_entity->body->value);
    $this->assertEmpty($first_entity->keywords->value);
    $this->assertEquals('item', $first_entity->bundle());
    $this->assertFalse($first_entity->highlighted->value);
    $publication_date = \DateTime::createFromFormat("Y-m-d\TH:i:s", $first_entity->publication_date->value);
    $cron_time = \DateTime::createFromFormat("Y-m-d\TH:i:s", $first_entity->cron_time->value);
    $this->assertEquals('2022-05-03 22:00:00', date('Y-m-d H:i:s', $publication_date->getTimestamp()));
    $this->assertEquals('2022-10-11 00:41:05', date('Y-m-d H:i:s', $cron_time->getTimestamp()));

    // Second remote item.
    $second_result = $result_items['https://demo.ec.europa.eu/entity-19'];
    $second_entity = $second_result->getOriginalObject()->getEntity();
    $this->assertEquals('remote item2', $second_entity->label());
    $this->assertEquals('bar iteM2. remote barista.', $second_entity->body->value);
    $this->assertEquals('item', $second_entity->bundle());
    $this->assertEquals([['value' => 'grape'], ['value' => 'pineapple']], $second_entity->keywords->getValue());
    $this->assertTrue($second_entity->highlighted->value);
    $publication_date = \DateTime::createFromFormat("Y-m-d\TH:i:s", $second_entity->publication_date->value);
    $cron_time = \DateTime::createFromFormat("Y-m-d\TH:i:s", $second_entity->cron_time->value);
    $this->assertEquals('2024-05-03 22:00:00', date('Y-m-d H:i:s', $publication_date->getTimestamp()));
    $this->assertEquals('2024-10-11 00:41:05', date('Y-m-d H:i:s', $cron_time->getTimestamp()));

    // Search by all articles.
    $query = $this->index->query();
    $query->addCondition('TYPE', 'article');
    $response = $query->execute();
    $result_items = $response->getResultItems();
    $this->assertCount(7, $result_items);
    $this->assertEquals(7, $response->getResultCount());

    // First local entity.
    $first_result = $result_items['https://demo.ec.europa.eu/entity-11'];
    $first_entity = $first_result->getOriginalObject()->getEntity();
    $this->assertEquals('article 1', $first_entity->label());
    $this->assertEquals('bar test casE', $first_entity->body->value);
    $this->assertEquals('article', $first_entity->bundle());
    $this->assertEquals([
      ['value' => 'orange'],
      ['value' => 'apple'],
      ['value' => 'grape'],
    ], $first_entity->keywords->getValue());
    $this->assertEmpty($first_entity->highlighted);

    // First remote entity.
    $first_result = $result_items['https://demo.ec.europa.eu/entity-16'];
    $first_entity = $first_result->getOriginalObject()->getEntity();
    $this->assertEquals('Remote article', $first_entity->label());
    $this->assertEquals('bar article', $first_entity->body->value);
    $this->assertEquals('article', $first_entity->bundle());
    $this->assertEquals([
      ['value' => 'orange'],
      ['value' => 'apple'],
      ['value' => 'grape'],
    ], $first_entity->keywords->getValue());
    $this->assertTrue($first_entity->highlighted->value);
    $publication_date = \DateTime::createFromFormat("Y-m-d\TH:i:s", $first_entity->publication_date->value);
    $cron_time = \DateTime::createFromFormat("Y-m-d\TH:i:s", $first_entity->cron_time->value);
    $this->assertEquals('2022-01-03 23:00:00', date('Y-m-d H:i:s', $publication_date->getTimestamp()));
    $this->assertEquals('2032-10-11 00:41:05', date('Y-m-d H:i:s', $cron_time->getTimestamp()));

    // Second remote entity.
    $second_result = $result_items['https://demo.ec.europa.eu/entity-18'];
    $second_entity = $second_result->getOriginalObject()->getEntity();
    $this->assertEquals('Remote article 2', $second_entity->label());
    $this->assertEquals('bar article 2. remote barista.', $second_entity->body->value);
    $this->assertEquals('article', $second_entity->bundle());
    $this->assertEquals([['value' => 'apple'], ['value' => 'grape']], $second_entity->keywords->getValue());
    $this->assertFalse($second_entity->highlighted->value);
    $publication_date = \DateTime::createFromFormat("Y-m-d\TH:i:s", $second_entity->publication_date->value);
    $cron_time = \DateTime::createFromFormat("Y-m-d\TH:i:s", $second_entity->cron_time->value);
    $this->assertEquals('2024-01-03 23:00:00', date('Y-m-d H:i:s', $publication_date->getTimestamp()));
    $this->assertEquals('2034-10-11 00:41:05', date('Y-m-d H:i:s', $cron_time->getTimestamp()));

    // Search by keys.
    $query = $this->index->query();
    $query->keys(['remote barista']);
    $response = $query->execute();
    $result_items = $response->getResultItems();
    $this->assertCount(2, $result_items);
    $this->assertEquals(2, $response->getResultCount());
    $result_keys = array_keys($result_items);
    $this->assertEquals([
      'https://demo.ec.europa.eu/entity-18',
      'https://demo.ec.europa.eu/entity-19',
    ], $result_keys);

    // Search with multiple filters.
    $query = $this->index->query();
    $query->addCondition('TYPE', 'item');
    $query->addCondition('keywords', 'pineapple');
    $response = $query->execute();
    $result_items = $response->getResultItems();
    $this->assertCount(1, $result_items);
    $this->assertEquals(1, $response->getResultCount());
    $result_keys = array_keys($result_items);
    $this->assertEquals(['https://demo.ec.europa.eu/entity-19'], $result_keys);
  }

  /**
   * Tests integration with views.
   */
  public function testView(): void {
    // Set index for local entity load.
    $this->index->setThirdPartySetting('oe_search', 'europa_search_entity_mode', 'local');
    $this->index->save();

    $this->createTestContent();

    // We need permissions to view entity test entities.
    $anonymous_role = Role::load(Role::ANONYMOUS_ID);
    $anonymous_role->grantPermission('view test entity');
    $anonymous_role->save();

    // Load the view and execute the views.
    // View is limited to page 1.
    $view = Views::getView('oe_search_europa_search_test');
    $view->setDisplay('default');
    $view->execute();
    // Compare the total.
    $this->assertEquals(15, $view->total_rows);
    // Compare the results.
    $this->assertCount(10, $view->result);
    // Check first result.
    $this->assertEquals('entity_test_mulrev_changed', $view->result[0]->_entity->getEntityTypeId());
    $this->assertEquals(1, $view->result[0]->_entity->id());
    $this->assertEquals('item 1', $view->result[0]->_entity->label());

    // Load the view and execute page 2.
    $view = Views::getView('oe_search_europa_search_test');
    $view->setDisplay('default');
    $view->setCurrentPage(1);
    $view->execute();
    // Compare the total.
    $this->assertEquals(15, $view->total_rows);
    // Compare the results.
    $this->assertCount(5, $view->result);
    // Check first result.
    $this->assertEquals('entity_test_mulrev_changed', $view->result[0]->_entity->getEntityTypeId());
    $this->assertEquals(11, $view->result[0]->_entity->id());
    $this->assertEquals('article 1', $view->result[0]->_entity->label());

    // Now execute the view with fulltext key.
    $view = Views::getView('oe_search_europa_search_test');
    $view->setDisplay('default');
    $view->setArguments(['all', 'barista']);
    $view->execute();
    $this->assertEquals(2, $view->total_rows);
    // Compare the results.
    $this->assertCount(2, $view->result);
    // Check first result.
    $this->assertEquals('entity_test_mulrev_changed', $view->result[0]->_entity->getEntityTypeId());
    $this->assertEquals(2, $view->result[0]->_entity->id());
    $this->assertEquals('item 2', $view->result[0]->_entity->label());

    // Now execute the view with bundle filter.
    // First with bundle "item".
    $view = Views::getView('oe_search_europa_search_test');
    $view->setDisplay('default');
    $view->setArguments(['item']);
    $view->execute();
    $this->assertEquals(12, $view->total_rows);
    $this->assertCount(10, $view->result);
    $this->assertEquals('entity_test_mulrev_changed', $view->result[0]->_entity->getEntityTypeId());
    $this->assertEquals(1, $view->result[0]->_entity->id());
    $this->assertEquals('item 1', $view->result[0]->_entity->label());

    // Now with bundle "article".
    $view = Views::getView('oe_search_europa_search_test');
    $view->setDisplay('default');
    $view->setArguments(['article']);
    $view->execute();
    $this->assertEquals(7, $view->total_rows);
    $this->assertCount(5, $view->result);
    $this->assertEquals('entity_test_mulrev_changed', $view->result[0]->_entity->getEntityTypeId());
    $this->assertEquals(11, $view->result[0]->_entity->id());
    $this->assertEquals('article 1', $view->result[0]->_entity->label());
  }

}
