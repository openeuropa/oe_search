<?php

declare(strict_types = 1);

namespace Drupal\oe_search\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\oe_search\Event\DocumentCreationEvent;
use Drupal\oe_search\IngestionDocument;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to add additional parsing to documents.
 */
class DocumentCreationSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a DocumentCreationSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      DocumentCreationEvent::class => 'setDocumentValues',
    ];
  }

  /**
   * Subscribes to the document creation event.
   *
   * @param \Drupal\oe_search\Event\DocumentCreationEvent $event
   *   The event object.
   */
  public function setDocumentValues(DocumentCreationEvent $event): void {
    $entity = $event->getEntity();
    if (!$entity instanceof FileInterface && !$entity instanceof MediaInterface) {
      return;
    }

    $document = $event->getDocument();
    $document->setCanBeIngested(TRUE);
    $document->setIngestionType(IngestionDocument::FILE_INGESTION);
    // Extract file entity from media if applicable.
    if ($entity instanceof MediaInterface) {
      $fid = $entity->getSource()->getSourceFieldValue($entity);
      $entity = $fid ? $this->entityTypeManager->getStorage('file')->load($fid) : NULL;
    }

    if ($entity instanceof FileInterface) {
      $document->setUrl($entity->createFileUrl(FALSE));
    }
  }

}
