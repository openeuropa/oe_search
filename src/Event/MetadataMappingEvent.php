<?php

declare(strict_types=1);

namespace Drupal\oe_search\Event;

use Drupal\search_api\Query\Query;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for managing metadata mapping.
 *
 * Subscribers to this event are able to change
 * the metadata mapping to the entities.
 */
class MetadataMappingEvent extends Event {

  /**
   * The metadata array.
   *
   * @var array
   */
  protected $metadata;

  /**
   * The index fields.
   *
   * @var array
   */
  protected $indexFields;

  /**
   * The values already mapped.
   *
   * @var array
   */
  protected $values;

  /**
   * The original query.
   *
   * @var \Drupal\search_api\Query\Query
   */
  protected $query;

  /**
   * Constructs a new MetadataMappingEvent object.
   *
   * @param \Drupal\search_api\Query\Query $query
   *   The query.
   * @param array $metadata
   *   The metadata array.
   * @param array $index_fields
   *   The index fields.
   * @param array $values
   *   The mapped values.
   */
  public function __construct(Query $query, array $metadata, array $index_fields, array $values) {
    $this->query = $query;
    $this->metadata = $metadata;
    $this->indexFields = $index_fields;
    $this->values = $values;
  }

  /**
   * Get the metadata.
   *
   * @return array
   *   The metadata.
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

  /**
   * Set the metadata.
   *
   * @param array $metadata
   *   The metadata.
   */
  public function setMetadata(array $metadata): void {
    $this->metadata = $metadata;
  }

  /**
   * Get the mapped values.
   *
   * @return array
   *   The mapped values.
   */
  public function getValues(): array {
    return $this->values;
  }

  /**
   * Sets the mapped values.
   *
   * @param array $values
   *   The mapped values.
   */
  public function setValues(array $values): void {
    $this->values = $values;
  }

  /**
   * Get the query.
   *
   * @return \Drupal\search_api\Query\Query
   *   The query.
   */
  public function getQuery(): Query {
    return $this->query;
  }

  /**
   * Set the query.
   *
   * @param \Drupal\search_api\Query\Query $query
   *   The query.
   */
  public function setQuery(Query $query): void {
    $this->query = $query;
  }

  /**
   * Get the index fields.
   *
   * @return array
   *   The index fields.
   */
  public function getIndexFields(): array {
    return $this->indexFields;
  }

  /**
   * Sets the index fields.
   *
   * @param array $indexFields
   *   The index Fields.
   */
  public function setIndexFields(array $indexFields): void {
    $this->indexFields = $indexFields;
  }

}
