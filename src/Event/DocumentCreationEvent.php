<?php

declare(strict_types=1);

namespace Drupal\oe_search\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\oe_search\IngestionDocument;
use Drupal\Component\EventDispatcher\Event;

/**
 * Event for managing document values.
 */
class DocumentCreationEvent extends Event {

  /**
   * The object.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The document.
   *
   * @var \Drupal\oe_search\IngestionDocument
   */
  protected $document;

  /**
   * Get the entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The source entity.
   */
  public function getEntity(): ContentEntityInterface {
    return $this->entity;
  }

  /**
   * Set the entity.
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
   * Get the document.
   *
   * @return \Drupal\oe_search\IngestionDocument
   *   The document to be ingested.
   */
  public function getDocument(): IngestionDocument {
    return $this->document;
  }

  /**
   * Set the document.
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
