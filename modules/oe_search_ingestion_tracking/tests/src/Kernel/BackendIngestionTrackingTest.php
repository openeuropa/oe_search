<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_search_ingestion_tracking\Kernel;

use Drupal\oe_search_ingestion_tracking\Entity\SearchIngestionTracking;
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

}
