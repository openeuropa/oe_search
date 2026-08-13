<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_search\Kernel;

use Drupal\oe_search_mock\EuropaSearchMockRequestCollector;
use Drupal\oe_search\Utility;
use Drupal\oe_search_mock\Config\EuropaSearchMockServerConfigOverrider;
use Psr\Http\Message\RequestInterface;

/**
 * Tests Europa Search Drupal Search API integration.
 *
 * @coversDefaultClass \Drupal\oe_search\Plugin\search_api\backend\SearchApiEuropaSearchBackend
 * @group oe_search
 */
class BackendIngestionTest extends BackendIngestionTestBase {

  /**
   * @covers ::deleteItems
   */
  public function testDeleteItems(): void {
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_DELETE, 0, 0);
    $this->server->deleteItems($this->index, $this->itemIds);
    // Only one token request as it gets cached after first request.
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_TOKEN, 1, 1, FALSE);
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_DELETE, 5, 5);
    // Compare sent data with received data.
    $requests = EuropaSearchMockRequestCollector::getCollectedRequests(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_DELETE);
    $this->assertCount(5, $requests);
    $this->assertDeletedItem($requests[0], 1);
    $this->assertDeletedItem($requests[1], 2);
    $this->assertDeletedItem($requests[2], 3);
    $this->assertDeletedItem($requests[3], 4);
    $this->assertDeletedItem($requests[4], 5);

    // The last item should have failed.
    // It should have a retry task in search_api_tasks.
    $this->assertEquals(1, $this->taskManager->getTasksCount());
    $tasks = $this->taskManager->loadTasks();
    $last_task = reset($tasks);
    $this->assertEquals('deleteItems', $last_task->getType());
  }

  /**
   * @covers ::deleteAllIndexItems
   */
  public function testDeleteAllIndexItems(): void {
    $this->backend->deleteAllIndexItems($this->index);
    // Only one token request as it gets cached after first request.
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_TOKEN, 1, 1, FALSE);
    $this->assertServiceMockCalls(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_DELETE_BY_QUERY, 1, 1);
    // Compare sent data with received data.
    $requests = EuropaSearchMockRequestCollector::getCollectedRequests(EuropaSearchMockServerConfigOverrider::ENDPOINT_INGESTION_DELETE_BY_QUERY);
    $this->assertCount(1, $requests);
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

}
