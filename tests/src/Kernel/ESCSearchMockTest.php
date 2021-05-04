<?php

namespace Drupal\Tests\oe_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\oe_search\EuropaSearchService;

/**
 * Tests the HTTP layer mocking.
 *
 * @group http_request_mock
 */
class ESCSearchMockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['oe_search'];

  /**
   * Tests Europa Search Client Search.
   */
  public function testSearchApi(): void {
    $controller = $this->getMockBuilder(EuropaSearchService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertNull($this->container->get('plugin.manager.service_mock')->getMatchingPlugin($controller->searchApi(), []));
  }

  /**
   * Tests Europa Search Client Ingestion.
   */
  public function testIngestionApi(): void {
    $controller = $this->getMockBuilder(EuropaSearchService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertNull($this->container->get('plugin.manager.service_mock')->getMatchingPlugin($controller->ingestionApi(), []));
  }

}
