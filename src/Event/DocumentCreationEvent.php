<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\oe_search\IngestionDocument;

/**
 * Event for managing document values.
 */
class DocumentCreationEvent extends Event {

  /**
   * The entity being ingested.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The ingestion document.
   *
   * @var \Drupal\oe_search\IngestionDocument
   */
  protected $document;

  /**
   * Returns the entity being ingested.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The source entity.
   */
  public function getEntity(): ContentEntityInterface {
    return $this->entity;
  }

  /**
   * Sets the entity being ingested.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The source entity.
   *
   * @return $this
   */
  public function setEntity(ContentEntityInterface $entity): self {
    $this->entity = $entity;
    return $this;
  }

  /**
   * Returns the ingestion document.
   *
   * @return \Drupal\oe_search\IngestionDocument
   *   The document to be ingested.
   */
  public function getDocument(): IngestionDocument {
    return $this->document;
  }

  /**
   * Sets the ingestion document.
   *
   * @param \Drupal\oe_search\IngestionDocument $document
   *   The document to be ingested.
   *
   * @return $this
   */
  public function setDocument(IngestionDocument $document): self {
    $this->document = $document;
    return $this;
  }

}
