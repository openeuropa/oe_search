<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_search_ingestion_tracking\Kernel;

use Drupal\oe_search_ingestion_tracking\Entity\SearchIngestionTracking;
use Drupal\oe_search_mock\Config\EuropaSearchMockServerConfigOverrider;
use Drupal\Tests\oe_search\Kernel\BackendIngestionTestBase;
use Psr\Http\Message\RequestInterface;

/**
 * Tests Europa Search Drupal Search API integration.
 *
 * Executes the same ingestions tests but tracks assertions.
 */
class BackendIngestionTrackingTest extends BackendIngestionTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'http_request_mock',
    'options',
    'oe_search',
    'oe_search_test',
    'oe_search_ingestion_tracking',
    'oe_search_mock',
    'search_api',
    'system',
    'user',
    'media',
    'image',
    'file',
  ];

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('oe_search_ingestion_tracking');
    $this->installConfig([
      'oe_search_ingestion_tracking',
    ]);

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface entityTypeManager */
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertIngestedItem(RequestInterface $request, array $items, int $id): void {
    parent::assertIngestedItem($request, $items, $id);
    $this->assertIngestionTracked($this->itemIds[$id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertIngestedFileItem(RequestInterface $request, array $items, int $id): void {
    parent::assertIngestedFileItem($request, $items, $id);
    $this->assertIngestionTracked($this->mediaItemIds[$id]);
  }

  /**
   * Assert the item ingestion has been tracked.
   *
   * @param string $item_id
   *   The id.
   */
  protected function assertIngestionTracked(string $item_id): void {
    if (preg_match('/^entity:([a-z_]+)\/(\d+):([a-z]{2,3}(?:-[a-z]{2,})?)$/i', $item_id, $matches)) {
      $properties = [
        'entity_type' => $matches[1],
        'entity_id' => $matches[2],
        'langcode' => $matches[3],
      ];
      $ingestions = $this->entityTypeManager->getStorage('oe_search_ingestion_tracking')->loadByProperties($properties);
      $this->assertCount(1, $ingestions);
      $ingestion = reset($ingestions);
      $this->assertEquals($matches[1], $ingestion->entity_type->value);
      $this->assertEquals($matches[2], $ingestion->entity_id->value);
      $this->assertEquals($matches[3], $ingestion->langcode->value);
      $this->assertNotEmpty($ingestion->tracking_id->value);
      $this->assertNotEmpty($ingestion->created->value);
      $this->assertEquals(SearchIngestionTracking::STATUS_PROCESSING, $ingestion->status->value);
      $this->assertEquals(0, $ingestion->retries->value);
    }

  }

  /**
   * Tests ingestion statuses processing.
   *
   * @covers \Drupal\oe_search_ingestion_tracking\IngestionTrackingStatusChecker::processUnfinishedIngestionsStatuses
   */
  public function testProcessUnfinishedIngestionsStatuses(): void {
    $checker = \Drupal::service('oe_search_ingestion_tracking.status_checker');
    $storage = $this->entityTypeManager->getStorage('oe_search_ingestion_tracking');

    \Drupal::database()->truncate('oe_search_ingestion_tracking');

    // Create 3 trackings.
    $this->createTrackingEntity('48134d43-df3f-4a5a-b420-0595237e970a', SearchIngestionTracking::STATUS_PROCESSING, 'entity_test_mulrev_changed', 1);
    $this->createTrackingEntity('48134d43-df3f-4a5a-b420-0595237e970b', SearchIngestionTracking::STATUS_PROCESSING, 'entity_test_mulrev_changed', 2);
    $this->createTrackingEntity('48134d43-df3f-4a5a-b420-0595237e970c', SearchIngestionTracking::STATUS_PROCESSING, 'entity_test_mulrev_changed', 3);

    $this->assertEquals(3, $storage->getAggregateQuery()->accessCheck(FALSE)->count()->execute());

    $checker->processUnfinishedIngestionsStatuses();
    // A single call is sent.
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_TRACKING_STATUS, 1, 1);
    $ingestion_trackings = $storage->loadMultiple(NULL);

    // Ids are correct.
    $this->assertEquals('48134d43-df3f-4a5a-b420-0595237e970a', $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970a']->id());
    $this->assertEquals('48134d43-df3f-4a5a-b420-0595237e970b', $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970b']->id());
    $this->assertEquals('48134d43-df3f-4a5a-b420-0595237e970c', $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970c']->id());

    // Statuses are correct.
    $this->assertEquals(SearchIngestionTracking::STATUS_PROCESSING, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970a']->get('status')->value);
    $this->assertEquals(SearchIngestionTracking::STATUS_FINISHED, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970b']->get('status')->value);
    $this->assertEquals(SearchIngestionTracking::STATUS_ERROR, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970c']->get('status')->value);

    // Retries are correct.
    $this->assertEquals(1, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970a']->get('retries')->value);
    $this->assertEquals(0, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970b']->get('retries')->value);
    $this->assertEquals(0, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970c']->get('retries')->value);

    // Processed are not empty.
    $processed_1 = $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970a']->get('processed')->value;
    $processed_2 = $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970a']->get('processed')->value;
    $processed_3 = $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970a']->get('processed')->value;
    $this->assertNotEmpty($processed_1);
    $this->assertNotEmpty($processed_2);
    $this->assertNotEmpty($processed_3);

    // Process again after minimal wait.
    sleep(1);

    $checker->processUnfinishedIngestionsStatuses();
    // A single call is sent.
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_TRACKING_STATUS, 1, 1);
    $ingestion_trackings = $storage->loadMultiple(NULL);

    // Statuses are the same.
    $this->assertEquals(SearchIngestionTracking::STATUS_PROCESSING, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970a']->get('status')->value);
    $this->assertEquals(SearchIngestionTracking::STATUS_FINISHED, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970b']->get('status')->value);
    $this->assertEquals(SearchIngestionTracking::STATUS_ERROR, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970c']->get('status')->value);

    // Retries are only increased in the one that didn't come back.
    $this->assertEquals(2, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970a']->get('retries')->value);
    $this->assertEquals(0, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970b']->get('retries')->value);
    $this->assertEquals(0, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970c']->get('retries')->value);

    // Processed time only vary in the one that was processed again.
    $this->assertNotEquals($processed_1, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970a']->get('processed')->value);
    $this->assertEquals($processed_2, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970b']->get('processed')->value);
    $this->assertEquals($processed_3, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970c']->get('processed')->value);

    // For the reamining item we reached the retries limit,
    // it won't be sent from now on.
    $checker->processUnfinishedIngestionsStatuses();
    // A single call is sent.
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_TRACKING_STATUS, 0, 0);
    $ingestion_trackings = $storage->loadMultiple(NULL);
    $this->assertEquals(2, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970a']->get('retries')->value);
    $this->assertEquals(0, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970b']->get('retries')->value);
    $this->assertEquals(0, $ingestion_trackings['48134d43-df3f-4a5a-b420-0595237e970c']->get('retries')->value);
  }

  /**
   * Creates a search ingestion tracking entity for testing purposes.
   *
   * @param string $tracking_id
   *   The tracking ID, which is also the entity ID.
   * @param int $status
   *   One of the SearchIngestionTracking::STATUS_* constants.
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The created entity id.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTrackingEntity(string $tracking_id, int $status, string $entity_type, int $entity_id): void {
    SearchIngestionTracking::create([
      'tracking_id' => $tracking_id,
      'index' => $this->index->id(),
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'langcode' => 'en',
      'created' => time(),
      'status' => $status,
    ])->save();
  }

}
