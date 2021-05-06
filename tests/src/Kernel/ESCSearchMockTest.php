<?php

namespace Drupal\Tests\oe_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\oe_search\EuropaSearchService;
use OpenEuropa\EuropaSearchClient\Model\Document;
use OpenEuropa\EuropaSearchClient\Model\Ingestion;
use OpenEuropa\EuropaSearchClient\Model\Search;
use OpenEuropa\EuropaSearchClient\Model\Token;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Tests the HTTP layer mocking.
 *
 * @group http_request_mock
 */
class ESCSearchMockTest extends KernelTestBase {

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['oe_search', 'http_request_mock'];

  /**
   * Tests Europa Search Client Search.
   */
  public function testSearchApi(): void {
    $document = new Document();
    $document->setAccessRestriction('true');
    $document->setApiVersion('string');
    $document->setContent('string');
    $document->setContentType('string');
    $document->setDatabase('string');
    $document->setDatabaseLabel('string');
    $document->setGroupById('string');
    $document->setLanguage('string');
    $document->setMetadata([
      'additionalProp1' => ["string"],
      'additionalProp2' => ["string"],
      'additionalProp3' => ["string"]
    ]);
    $document->setPages(0);
    $document->setReference('string');
    $document->setSummary('string');
    $document->setTitle('string');
    $document->setUrl('string');
    $document->setWeight(1.1);
    $document->setChildren([]);

    $documents = [$document];

    $expected = new Search();
    $expected->setResults($documents);

    $actual_docs = [];

    $this->container->get('state')->set('http_request_mock.allowed_plugins', [
      'rest_search',
    ]);

    $http_client = $this->container->get('http_client');
    $response = $http_client->get('http://api.com/search');
    $actual = json_decode($response->getBody()->getContents(), true);
    foreach ($actual as $item) {
      $actual_docs[] = $this->getSerializer()->denormalize($item, Document::class);
    }

    // Mock EuropaSearchService service method searchApi
    $serviceMock = $this->getMockBuilder(EuropaSearchService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $serviceMock->expects($this->any())
      ->method('searchApi')
      ->will($this->returnValue($actual_docs));

    $this->assertEquals($expected->getResults(), $serviceMock->searchApi());
  }

  /**
   * Tests Europa Search Client Ingestion.
   */
  public function testIngestionApi(): void {
    $expected = new Ingestion();
    $expected->setApiVersion('string');
    $expected->setReference('string');
    $expected->setTrackingId('string');

    $this->container->get('state')->set('http_request_mock.allowed_plugins', [
      'rest_ingestion',
    ]);

    $http_client = $this->container->get('http_client');
    $response = $http_client->get('http://api.com/ingestion');
    $actual = json_decode($response->getBody()->getContents());
    $actual = $this->getSerializer()->denormalize($actual, Ingestion::class);

    // Mock EuropaSearchService service method ingestionApi
    $serviceMock = $this->getMockBuilder(EuropaSearchService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $serviceMock->expects($this->any())
      ->method('ingestionApi')
      ->will($this->returnValue($actual));

    $this->assertEquals($expected, $serviceMock->ingestionApi());
  }

  /**
   * Returns a configured serializer to convert API responses.
   *
   * @return SerializerInterface
   *   The serializer.
   */
  protected function getSerializer(): SerializerInterface
  {
    if ($this->serializer === null) {
      $this->serializer = new Serializer([
        new GetSetMethodNormalizer(null, null, new PhpDocExtractor()),
        new ArrayDenormalizer(),
      ], [
        new JsonEncoder(),
      ]);
    }

    return $this->serializer;
  }

}
