<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_search\Unit;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\oe_search\Plugin\search_api\backend\SearchApiEuropaSearchBackend;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Tests functionality of the backend.
 *
 * @coversDefaultClass \Drupal\oe_search\Plugin\search_api\backend\SearchApiEuropaSearchBackend
 */
class SearchApiEuropaSearchBackendTest extends UnitTestCase {

  /**
   * OpenEuropa Search backend plugin.
   *
   * @var \Drupal\oe_search\Plugin\search_api\backend\SearchApiEuropaSearchBackend
   */
  protected $backend;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $http_client = $this->getMockClient();
    $this->backend = new SearchApiEuropaSearchBackend([
      'api_key' => 'api-key',
      'enable_ingestion' => TRUE,
      'database' => 'db',
      'text_ingestion_api_endpoint' => 'https://example.com/ingestion/text',
      'file_ingestion_api_endpoint' => 'https://example.com/ingestion',
      'delete_api_endpoint'  => 'https://example.com/ingestion/document',
      'token_api_endpoint' => 'https://example.com/token',
      'search_api_endpoint' => 'https://example.com/search',
      'facets_api_endpoint' => 'https://example.com/search/facets',
      'info_api_endpoint' => 'https://example.com/search/info',
    ], NULL, [],
      $http_client,
      new Settings([
        'oe_search' => [
          'backend' => [
            'oe_search_test_server' => [
              'consumer_key' => 'test-key',
              'consumer_secret' => 'test-secret',
            ],
          ],
        ],
      ]),
      $this->prophesize(LanguageManagerInterface::class)->reveal()
    );

    $server = $this->createMock(Server::class);
    $server->method('id')->willReturn('oe_search_test_server');
    $this->backend->setServer($server);
  }

  /**
   * @covers ::isAvailable
   */
  public function testIsAvailable() {
    $actual = $this->backend->isAvailable();
    $this->assertSame(TRUE, $actual);
  }

  /**
   * @covers ::indexItems
   * @throws \Drupal\search_api\SearchApiException
   */
  public function testIndexItems() {
    $index = $this->createMock(IndexInterface::class);
    $index->method('id')->willReturn('oe_search_test_index');
    $item = $this->createMock(ItemInterface::class);
    $item->method('getLanguage')->willReturn('en');
    $item->method('getOriginalObject')->willReturn(new MockOriginalObject());

    $actual = $this->backend->indexItems($index, ['1' => $item]);
    $this->assertEquals(['1'], $actual);
  }

  /**
   * @covers ::deleteItems
   * @throws \Drupal\search_api\SearchApiException
   */
  public function testDeleteItems() {
    $index = $this->createMock(IndexInterface::class);
    $index->method('id')->willReturn('oe_search_test_index');
    $this->backend->deleteItems($index, []);
  }

  /**
   * Mock server responses in a http client.
   *
   * @return \GuzzleHttp\Client
   *   The http client.
   */
  protected function getMockClient() {
    $queue = [
      // Token response.
      new Response(200, [], json_encode([
        'access_token' => 'JWT_TOKEN',
        'scope' => 'APPLICATION_SCOPE',
        'token_type' => 'Bearer',
        'expires_in' => 3600,
      ])),
      // Ingest text response.
      new Response(200, [], json_encode([
        'api_version' => '2.31',
        'reference' => 'ref1',
        'tracking_id' => 'd426d72b',
      ])),
    ];
    $mock = new MockHandler($queue);
    $stack = HandlerStack::create($mock);

    return new Client(['handler' => $stack]);
  }

}

/**
 * Class to mock a object to ingest.
 *
 * @package Drupal\Tests\oe_search\Unit
 */
class MockOriginalObject {

  /**
   * Stub method.
   *
   * @return $this
   */
  public function getValue() {
    return $this;
  }

  /**
   * Stub method.
   *
   * @return $this
   */
  public function toUrl() {
    return $this;
  }

  /**
   * Stub method.
   *
   * @return $this
   */
  public function setAbsolute() {
    return $this;
  }

  /**
   * Stub method.
   *
   * @return string
   *   A stub url.
   */
  public function toString() {
    return 'http://mock-orioginal-object.eu';
  }

  /**
   * Stub method.
   *
   * @return string
   *   A stub label.
   */
  public function label() {
    return 'test label';
  }

}
