<?php

namespace Drupal\Tests\oe_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\oe_search\EuropaSearchService;
use OpenEuropa\EuropaSearchClient\Model\Document;
use OpenEuropa\EuropaSearchClient\Model\Search;

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
    $document = new Document();
    $document->setAccessRestriction('true');
    $document->setApiVersion('string');
    $document->setContent('string');
    $document->setContent('string');
    $document->setDatabase('string');
    $document->setDatabaseLabel('string');
    $document->setGroupById('string');
    $document->setLanguage('string');
    $document->setMetadata([
      'additionalProp1' => ["string"],
      'additionalProp2' => ["string"]
    ]);
    $document->setPages(0);
    $document->setReference('string');
    $document->setSummary('string');
    $document->setTitle('string');
    $document->setUrl('string');
    $document->setWeight(0);

    $documents = [$document];

    // Mock EuropaSearchService service method searchApi
    $serviceMock = $this->getMockBuilder(EuropaSearchService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $serviceMock->expects($this->any())
      ->method('searchApi')
      ->will($this->returnValue($documents));

    $expected = new Search();
    $expected->setResults($documents);

    $this->assertEquals($expected->getResults(), $serviceMock->searchApi());
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
