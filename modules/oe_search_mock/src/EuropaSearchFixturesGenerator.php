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

    $entities[16] = [
      'name' => 'Remote article',
      'body' => 'bar article',
      'type' => 'article',
      'language' => 'en',
      'keywords' => ['orange', 'apple', 'grape'],
      'highlighted' => 'true',
      'publication_date' => '2022-01-03T13:00:00.000+0100',
      'cron_time' => '2032-10-10T15:41:05.000+0200',
    ];

    $entities[17] = [
      'name' => 'remote item',
      'body' => 'bar iteM',
      'type' => 'item',
      'language' => 'en',
      'highlighted' => 'false',
      'publication_date' => '2022-05-03T13:00:00.000+0100',
      'cron_time' => '2022-10-10T15:41:05.000+0200',
    ];

    $entities[18] = [
      'name' => 'Remote article 2',
      'body' => 'bar article 2. remote barista.',
      'type' => 'article',
      'language' => 'en',
      'keywords' => ['apple', 'grape'],
      'highlighted' => 'false',
      'publication_date' => '2024-01-03T13:00:00.000+0100',
      'cron_time' => '2034-10-10T15:41:05.000+0200',
    ];

    $entities[19] = [
      'name' => 'remote item2',
      'body' => 'bar iteM2. remote barista.',
      'type' => 'item',
      'language' => 'en',
      'highlighted' => 'true',
      'keywords' => ['grape', 'pineapple'],
      'publication_date' => '2024-05-03T13:00:00.000+0100',
      'cron_time' => '2024-10-10T15:41:05.000+0200',
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
    $bundle = $info['bundle'] ?? '';

    // If we have multiple bundles (to limit allowed content types) we should
    // not restrict bundles in mock (to let limit in filters only).
    if (is_array($bundle)) {
      $bundle = count($bundle) > 1 ? '' : $bundle[0];
    }

    if (!empty($filters['LANGUAGE_WITH_FALLBACK'])) {
      $filters['LANGUAGE_WITH_FALLBACK'] = is_array($filters['LANGUAGE_WITH_FALLBACK']) ? $filters['LANGUAGE_WITH_FALLBACK'][0] : $filters['LANGUAGE_WITH_FALLBACK'];
    }

    return static::buildSearchScenario($id, $filters, $entity_type, $bundle);
  }

  /**
   * Returns the JSON response for the facets given the filters.
   *
   * @param array $filters
   *   The filters.
   *
   * @return string|null
   *   The response.
   */
  public static function getFacetsJson(array $filters): ?string {
    $path = static::getFixturesBasePath();
    if (!$filters) {
      return file_get_contents($path . '/empty.json');
    }

    $info = static::getMockInfoFromFilters($filters);
    if (!$info) {
      return file_get_contents($path . '/empty.json');
    }

    $id = $info['id'];
    return static::buildFacetsScenario($id, $filters);
  }

  /**
   * Builds the given facets scenario.
   *
   * @param string $scenario_id
   *   The scenario ID.
   * @param array $filters
   *   The filters.
   *
   * @return string
   *   The response json.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  protected static function buildFacetsScenario(string $scenario_id, array $filters): string {
    $path = static::getFixturesBasePath();
    // Get the wrapper that works for all scenarios.
    $wrapper = file_get_contents($path . '/wrapper_facets.json');
    $json = json_decode($wrapper, TRUE);
    // Set the query search terms that work for all scenarios.
    $json['terms'] = $filters['TEXT'];

    $original_facets = [];
    $original_facets['site_name'] = static::buildFacet('SITE_NAME', [
      [
        'value' => 'oe_search_demo',
        'count' => 2,
      ],
      [
        'value' => 'site_2_demo',
        'count' => 1,
      ],
    ]);
    $original_facets['type'] = static::buildFacet('TYPE', [
      [
        'value' => 'item',
        'count' => 12,
      ],
      [
        'value' => 'article',
        'count' => 7,
      ],
    ]);

    switch ($scenario_id) {
      // Both facets indicated.
      case '1bcb0601faf614dbd87a2db4bfc8b04c':
        $json['facets'][] = $original_facets['site_name'];
        $json['facets'][] = $original_facets['type'];
        break;

      // Facet for site name.
      case '43244d9601170787ead159579fe2378e':
        $json['facets'][] = $original_facets['site_name'];
        break;
    }

    return json_encode($json);
  }

  /**
   * Builds a facet with name and values.
   *
   * @param string $name
   *   The facet name.
   * @param array $values
   *   The facet values.
   *
   * @return array
   *   The built facet.
   */
  protected static function buildFacet(string $name, array $values) : array {
    $facet = [
      "apiVersion" => '1.34',
      "name" => $name,
      "rawName" => $name,
      "database" => "EUROPA_SEARCH_DEMO",
    ];

    $facet['count'] = count($values);
    if (empty($values)) {
      return $facet;
    }

    $facet['values'] = [];
    foreach ($values as $value) {
      $facet['values'][] = [
        "apiVersion" => '1.34',
        "rawValue" => $value['value'],
        "value" => $value['value'],
        "count" => $value['count'],
      ];
    }

    return $facet;
  }

  /**
   * Builds the given search scenario.
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
  protected static function buildSearchScenario(string $scenario_id, array $filters, string $entity_type, string $bundle = ''): string {
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

      // All entities. No filters.
      case '1cc193e0d8cb8cc5cd1423f032453ea0':
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
      case '8d57bb64c43dfec6266ae3b5f58fae5c':
        $entities = static::filterEntities([2, 3]);
        $json['results'] = $entities;
        $json['totalResults'] = 2;
        break;

      // Filter by type item, page 1.
      case '55b66d3c554b10ea6266b4398dd5a872':
        $entities = static::filterEntities([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        $json['results'] = $entities;
        $json['totalResults'] = 12;
        break;

      // Filter by type item, limit 5 results.
      case 'a59df15adf1e403a72a2f866109b7cac':
        $entities = static::filterEntities([1, 2, 3, 4, 5]);
        $json['results'] = $entities;
        $json['totalResults'] = 12;
        break;

      // Filter by type item, page 2.
      case '8cf0d00d710f647afb004c0d5fb6c695':
        $entities = static::filterEntities([17, 19]);
        $json['results'] = $entities;
        $json['totalResults'] = 12;
        break;

      // Filter by type article, remote.
      case 'dfc89f7298b9febe2ba79b0da58aff39':
        $entities = static::filterEntities([11, 12, 13, 14, 15, 16, 18]);
        $json['results'] = $entities;
        $json['totalResults'] = 7;
        break;

      // Filter by text, remote barista, no filters.
      case '24ae3b1985eb241a688ca6afb28a4b9e':
        $entities = static::filterEntities([18, 19]);
        $json['results'] = $entities;
        $json['totalResults'] = 2;
        break;

      // Filter by type item, keywords pineapple.
      case 'd7af151c74fbe829da9e6f80ccd2211f':
        $entities = static::filterEntities([19]);
        $json['results'] = $entities;
        $json['totalResults'] = 1;
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
        'NAME' => [$result['name']],
        'TYPE' => [$result['type']],
        'BODY' => [$result['body']],
        'LANGUAGE' => [$result['language']],
        'KEYWORDS' => [$result['keywords'] ?? ''],
        'CATEGORY' => [$result['category'] ?? ''],
        'HIGHLIGHTED' => [$result['highlighted'] ?? ''],
        'PUBLICATION_DATE' => [$result['publication_date'] ?? ''],
        'CRON_TIME' => [$result['cron_time'] ?? ''],
        'SEARCH_API_ID' => ['entity:entity_test_mulrev_changed' . '/' . $id . ':' . $language],
        'SEARCH_API_DATASOURCE' => ['entity:entity_test_mulrev_changed'],
        'SEARCH_API_LANGUAGE' => [$language],
      ];

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
