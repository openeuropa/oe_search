<?php

declare(strict_types = 1);

namespace Drupal\oe_search_mock;

use Drupal\oe_search\Utility;

/**
 * Generates ES search responses.
 */
class EuropaSearchFixturesGenerator {

  use EuropaSearchMockTrait;

  /**
   * Return an array of entities.
   *
   * @return array
   *   The entities.
   */
  public static function getEntities(): array {
    $entities[1] = [
      'name' => 'item 1',
      'body' => 'bar test casE',
      'type' => 'item',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[2] = [
      'name' => 'item 2',
      'body' => 'barista test casE',
      'type' => 'item',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[3] = [
      'name' => 'item 3',
      'body' => 'barista test casE',
      'type' => 'item',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[4] = [
      'name' => 'item 4',
      'body' => 'bar test casE',
      'type' => 'item',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[5] = [
      'name' => 'item 5',
      'body' => 'bar test casE',
      'type' => 'item',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[6] = [
      'name' => 'item 6',
      'body' => 'bar test casE',
      'type' => 'item',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[7] = [
      'name' => 'item 7',
      'body' => 'bar test casE',
      'type' => 'item',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[8] = [
      'name' => 'item 8',
      'body' => 'bar test casE',
      'type' => 'item',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[9] = [
      'name' => 'item 9',
      'body' => 'bar test casE',
      'type' => 'item',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[10] = [
      'name' => 'item 10',
      'body' => 'bar test casE',
      'type' => 'item',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[11] = [
      'name' => 'article 1',
      'body' => 'bar test casE',
      'type' => 'article',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[12] = [
      'name' => 'article 2',
      'body' => 'bar test casE',
      'type' => 'article',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[13] = [
      'name' => 'article 3',
      'body' => 'bar test casE',
      'type' => 'article',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[14] = [
      'name' => 'article 4',
      'body' => 'bar test casE',
      'type' => 'article',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];
    $entities[15] = [
      'name' => 'article 5',
      'body' => 'bar test casE',
      'type' => 'article',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ];

    return $entities;
  }

  /**
   * Returns the JSON response for the search given the filters.
   *
   * @param array $filters
   *   The filters.
   *
   * @return string|null
   *   The response.
   */
  public static function getSearchJson(array $filters): ?string {
    $path = static::getFixturesBasePath();
    if (!$filters) {
      return file_get_contents($path . '/empty.json');
    }

    $info = static::getMockInfoFromFilters($filters);
    if (!$info) {
      return file_get_contents($path . '/empty.json');
    }

    $id = $info['id'];
    $entity_type = $info['entity_type'];
    $bundle = $info['bundle'];

    return static::buildScenario($id, $filters, $entity_type, $bundle);
  }

  /**
   * Builds the given scenario.
   *
   * @param string $scenario_id
   *   The scenario ID.
   * @param array $filters
   *   The filters.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return string
   *   The response json.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  protected static function buildScenario(string $scenario_id, array $filters, string $entity_type, $bundle = ''): string {
    $path = static::getFixturesBasePath();
    $language = $filters['LANGUAGE_WITH_FALLBACK'] ?? 'en';

    // Get the wrapper that works for all scenarios.
    $wrapper = file_get_contents($path . '/wrapper.json');
    $json = json_decode($wrapper, TRUE);
    $json['queryLanguage']['language'] = $language;
    // Set the query search terms that work for all scenarios.
    $json['terms'] = $filters['TEXT'];

    switch ($scenario_id) {

      /*
       * Queries items, no filters.
       */
      // Used for deleteItems() test, all items, no pagination.
      // Used for search view, no contextual filters, page 1.
      case '521068af216646bdd2338ecf7f7b0db9':
      case '22718631383982236b8f3940b7167c9a':
        $entities = static::filterEntities([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        $json['results'] = $entities;
        $json['totalResults'] = 15;
        break;

      // Used for search view, no contextual filters, page 2.
      case '293cdb5d284b50bb29302f205fee2f24':
      case '422d0b6d241e9e08f0c1763e53dde6d9':
        $entities = static::filterEntities([11, 12, 13, 14, 15]);
        $json['results'] = $entities;
        $json['totalResults'] = 15;
        break;

      // Filter by type = item.
      case 'b7f732953e01d78a89936317e08779e3':
      case '2c03c8d35e13b542e1d21aa5801f7a41':
        $entities = static::filterEntities([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        $json['results'] = $entities;
        $json['totalResults'] = 10;
        break;

      // Filter by type = article.
      case '760af89bb2eead3c40e789818dde8bec':
        $entities = static::filterEntities([11, 12, 13, 14, 15]);
        $json['results'] = $entities;
        $json['totalResults'] = 5;
        break;

      // Filter by text = barista.
      case 'ef178f7bcda0e0d0b286bcff9b1cf581':
        $entities = static::filterEntities([2, 3]);
        $json['results'] = $entities;
        $json['totalResults'] = 2;
        break;

    }

    return json_encode($json);
  }

  /**
   * Returns the list of filtered entities.
   *
   * @param array|null $ids
   *   The ids.
   *
   * @return array
   *   The filtered entities.
   */
  public static function filterEntities($ids = NULL) : array {
    $entities = static::getEntities();
    if (!empty($ids)) {
      $entities = array_filter($entities, function ($entity, $id) use ($ids) {
        return in_array($id, $ids);
      }, ARRAY_FILTER_USE_BOTH);
    }

    return static::getFormattedEntities($entities);
  }

  /**
   * Get the entities formatted as ES results.
   *
   * @param array $entities
   *   The entities.
   *
   * @return array
   *   The formatted entities.
   */
  protected static function getFormattedEntities(array $entities): array {
    $json_entities = [];
    foreach ($entities as $id => $result) {
      $language = $result['language'];
      $entity = new \stdClass();
      $entity->apiVersion = "2.91";
      $entity->reference = Utility::getSiteHash() . "-europa_search_index-entity:entity_test_mulrev_changed" . "/" . $id . ':en';
      $entity->url = "https://demo.ec.europa.eu/entity-" . $id;
      $entity->title = $result['name'];
      $entity->contentType = "text/plain";
      $entity->language = $language;
      $entity->databaseLabel = 'OE_SEARCH_DEMO';
      $entity->database = 'OE_SEARCH_DEMO';
      $entity->content = "xxxxx";
      $entity->metadata = [
        'title' => [$result['name']],
        'type' => [$result['type']],
        'SEARCH_API_ID' => ['entity:entity_test_mulrev_changed' . '/' . $id . ':' . $language],
        'SEARCH_API_DATASOURCE' => ['entity:entity_test_mulrev_changed'],
        'SEARCH_API_LANGUAGE' => [$language],
      ];
      // $entity->metadata += $extra_metadata;
      $entity->children = [];
      $json_entities[] = $entity;
    }
    return $json_entities;
  }

  /**
   * Returns the base path for the fixtures folder.
   *
   * @return string
   *   The path.
   */
  public static function getFixturesBasePath(): string {
    return \Drupal::service('extension.path.resolver')->getPath('module', 'oe_search_mock') . '/responses/json';
  }

}
