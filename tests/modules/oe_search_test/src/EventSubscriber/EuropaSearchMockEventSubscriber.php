<?php

declare(strict_types = 1);

namespace Drupal\oe_search_test\EventSubscriber;

use Drupal\oe_search\Utility;
use Drupal\oe_search_mock\EuropaSearchMockEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to Drupal\oe_search_mock\EuropaSearchMockEvent event.
 */
class EuropaSearchMockEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EuropaSearchMockEvent::EUROPA_SEARCH_MOCK_EVENT][] = [
      'setMockResources',
      -1,
    ];
    return $events;
  }

  /**
   * Sets a new JSON resource.
   *
   * @param \Drupal\oe_search_mock\EuropaSearchMockEvent $event
   *   The event.
   */
  public function setMockResources(EuropaSearchMockEvent $event): void {
    $resources = $event->getResources();

    // Adapt mocked search json for test module.
    $index_id = 'europa_search_index';
    $resources['simple_search_response'] = json_encode([
      'apiVersion' => '2.69',
      'terms' => '',
      'responseTime' => 44,
      'totalResults' => 5,
      'pageNumber' => 1,
      'pageSize' => 10,
      'sort' => 'title:ASC',
      'groupByField' => NULL,
      'queryLanguage' => [
        'language' => 'en',
        'probability' => 0.0,
      ],
      'spellingSuggestion' => '',
      'bestBets' => [],
      'results' => [
        [
          'apiVersion' => '2.69',
          'reference' => Utility::createReference($index_id, 'entity:entity_test_mulrev_changed/1:en'),
          'url' => 'http://example.com/ref1',
          'title' => NULL,
          'contentType' => 'text/plain',
          'language' => 'en',
          'databaseLabel' => 'ACME',
          'database' => 'ACME',
          'summary' => NULL,
          'weight' => 9.849739,
          'groupById' => '3',
          'content' => 'A coordination platform',
          'accessRestriction' => FALSE,
          'pages' => NULL,
          'metadata' => [],
          'children' => [],
        ],
        [
          'apiVersion' => '2.69',
          'reference' => Utility::createReference($index_id, 'entity:entity_test_mulrev_changed/2:en'),
          'url' => 'http://example.com/ref2',
          'title' => NULL,
          'contentType' => 'text/plain',
          'language' => 'en',
          'databaseLabel' => 'ACME',
          'database' => 'ACME',
          'summary' => NULL,
          'weight' => 9.849739,
          'groupById' => '3',
          'content' => 'A coordination platform',
          'accessRestriction' => FALSE,
          'pages' => NULL,
          'metadata' => [],
          'children' => [],
        ],
        [
          'apiVersion' => '2.69',
          'reference' => Utility::createReference($index_id, 'entity:entity_test_mulrev_changed/3:en'),
          'url' => 'http://example.com/ref3',
          'title' => NULL,
          'contentType' => 'text/plain',
          'language' => 'en',
          'databaseLabel' => 'ACME',
          'database' => 'ACME',
          'summary' => NULL,
          'weight' => 9.849739,
          'groupById' => '3',
          'content' => 'A coordination platform',
          'accessRestriction' => FALSE,
          'pages' => NULL,
          'metadata' => [],
          'children' => [],
        ],
        [
          'apiVersion' => '2.69',
          'reference' => Utility::createReference($index_id, 'entity:entity_test_mulrev_changed/4:en'),
          'url' => 'http://example.com/ref4',
          'title' => NULL,
          'contentType' => 'text/plain',
          'language' => 'en',
          'databaseLabel' => 'ACME',
          'database' => 'ACME',
          'summary' => NULL,
          'weight' => 9.849739,
          'groupById' => '3',
          'content' => 'A coordination platform',
          'accessRestriction' => FALSE,
          'pages' => NULL,
          'metadata' => [],
          'children' => [],
        ],
        [
          'apiVersion' => '2.69',
          'reference' => Utility::createReference($index_id, 'entity:entity_test_mulrev_changed/5:en'),
          'url' => 'http://example.com/ref5',
          'title' => NULL,
          'contentType' => 'text/plain',
          'language' => 'en',
          'databaseLabel' => 'ACME',
          'database' => 'ACME',
          'summary' => NULL,
          'weight' => 9.849739,
          'groupById' => '3',
          'content' => 'A coordination platform',
          'accessRestriction' => FALSE,
          'pages' => NULL,
          'metadata' => [],
          'children' => [],
        ],
      ],
      'warnings' => [],
    ]);
    $event->setResources($resources);
  }

}
