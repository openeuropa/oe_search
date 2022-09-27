<?php

declare(strict_types=1);

namespace Drupal\oe_search\Event;

use Drupal\search_api\Item\Field;
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
   * The metadata key.
   *
   * @var string
   */
  protected $metadataKey;

  /**
   * The field.
   *
   * @var Drupal\search_api\Item\Field
   */
  protected $field;

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
   * @param string $metadata_key
   *   The metadata key.
   * @param \Drupal\search_api\Item\Field $field
   *   The search api field.
   * @param array $values
   *   The mapped values.
   */
  public function __construct(Query $query, array $metadata, string $metadata_key, Field $field, array $values) {
    $this->query = $query;
    $this->metadata = $metadata;
    $this->metadataKey = $metadata_key;
    $this->field = $field;
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
   * Get the metadata key.
   *
   * @return string
   *   The metadata key.
   */
  public function getMetadataKey(): string {
    return $this->metadataKey;
  }

  /**
   * Set the metadata key.
   *
   * @param string $metadataKey
   *   The metadata key.
   */
  public function setMetadataKey(string $metadataKey): void {
    $this->metadataKey = $metadataKey;
  }

  /**
   * Get the field.
   *
   * @return \Drupal\search_api\Item\Field
   *   The field.
   */
  public function getField(): Field {
    return $this->field;
  }

  /**
   * Set the field.
   *
   * @param \Drupal\search_api\Item\Field $field
   *   The field.
   */
  public function setField(Field $field): void {
    $this->field = $field;
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

}
