<?php

declare(strict_types = 1);

namespace Drupal\oe_search;

use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api\Query\Query;

/**
 * Specific utility methods.
 */
class Utility {

  /**
   * Returns a unique hash for the current site.
   *
   * This is used to identify the Europa Search documents from different sites
   * within a single Europa Search database.
   *
   * @return string
   *   A unique site hash, containing only alphanumeric characters.
   */
  public static function getSiteHash(): string {
    $state = \Drupal::state();
    if (!$hash = $state->get('oe_search.site_hash')) {
      global $base_url;
      $hash = substr(base_convert(hash('sha256', uniqid($base_url, TRUE)), 16, 36), 0, 6);
      $state->set('oe_search.site_hash', $hash);
    }
    return $hash;
  }

  /**
   * Creates an ID used as the unique identifier at the Europa Search server.
   *
   * This method should be used everywhere we need to get the Europa Search
   * reference for a given Search API item ID. The way it's constructed
   * guarantees that we can ingest content from different sites and indexes in
   * the same Europa Search database.
   *
   * @param string $index_id
   *   The index ID.
   * @param string $item_id
   *   The item ID.
   *
   * @return string
   *   A unique Europa Search reference for the given item.
   */
  public static function createReference(string $index_id, string $item_id): string {
    return static::getSiteHash() . "-{$index_id}-{$item_id}";
  }

  /**
   * Extracts the item ID from the document reference.
   *
   * @param string $reference
   *   The document reference.
   *
   * @return array
   *   The deconstructed reference.
   */
  public static function destructReference(string $reference): array {
    return explode('-', $reference);
  }

  /**
   * Get original field name for ES.
   *
   * Uppercase the field name in case uppercase processor is added.
   *
   * @param string $field
   *   The original field name.
   * @param \Drupal\search_api\Query\Query $query
   *   The query.
   *
   * @return string
   *   The translated field name.
   */
  public static function getEsFieldName(string $field, Query $query): string {
    $processors = $query->getIndex()->getProcessorsByStage(ProcessorInterface::STAGE_PREPROCESS_INDEX);
    if (!empty($processors['europa_search_uppercase_field_names'])) {
      $field = strtoupper($field);
    }
    return $field;
  }

}
