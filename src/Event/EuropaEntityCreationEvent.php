<?php

declare(strict_types=1);

namespace Drupal\oe_search\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\search_api\Query\Query;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for europa entity creation.
 *
 * Subscribers to this event are able to change
 * entity created from metadata mapping.
 */
class EuropaEntityCreationEvent extends Event {

  /**
   * The created entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The metadata array.
   *
   * @var array
   */
  protected $metadata;

  /**
   * The original query.
   *
   * @var \Drupal\search_api\Query\Query
   */
  protected $query;

  /**
   * Constructs a new EuropaEntityCreationEvent object.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The  created entity.
   * @param array $metadata
   *   The metadata array.
   * @param \Drupal\search_api\Query $query
   *   The search api query.
   */
  public function __construct(ContentEntityInterface $entity, array $metadata, Query $query) {
    $this->entity = $entity;
    $this->metadata = $metadata;
    $this->query = $query;
  }

  /**
   * Gets the metadata.
   *
   * @return array
   *   The metadata.
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

  /**
   * Sets the metadata.
   *
   * @param array $metadata
   *   The metadata.
   */
  public function setMetadata(array $metadata): void {
    $this->metadata = $metadata;
  }

  /**
   * Gets the query.
   *
   * @return \Drupal\search_api\Query\Query
   *   The query.
   */
  public function getQuery(): Query {
    return $this->query;
  }

  /**
   * Sets the query.
   *
   * @param \Drupal\search_api\Query\Query $query
   *   The query.
   */
  public function setQuery(Query $query): void {
    $this->query = $query;
  }

  /**
   * Gets the entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  public function getEntity(): ContentEntityInterface {
    return $this->entity;
  }

  /**
   * Sets the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public function setEntity(ContentEntityInterface $entity): void {
    $this->entity = $entity;
  }

}
