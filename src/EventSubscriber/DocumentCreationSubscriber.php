<?php

declare(strict_types = 1);

namespace Drupal\oe_search\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\oe_search\Event\DocumentCreationEvent;
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
   * NotificationsController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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

    switch (TRUE) {
      case $entity instanceof FileInterface:
        // @todo decide if files are supported.
        $this->setFileFields($entity, $event);
        break;

      case $entity instanceof MediaInterface:
        $fid = $entity->getSource()->getSourceFieldValue($entity);
        $file = $this->entityTypeManager->getStorage('file')->load($fid);

        // @todo decide what action to take for remote files.
        if ($file === NULL) {
          continue;
        }
        // @todo decide if standalone URL should be used if enabled.
        $this->setFileFields($file, $event);
        break;

      default:
        // @todo if files are removed then move this back into the backend.
        $event->getDocument()->setUrl($entity->toUrl()->setAbsolute()->toString());
        break;
    }
  }

  /**
   * Set document fields required for file ingestion.
   *
   * @param \Drupal\oe_search\EventSubscriber\File $file
   *   The file entity.
   * @param \Drupal\oe_search\Event\DocumentCreationEvent $event
   *   The event object.
   */
  protected function setFileFields(File $file, DocumentCreationEvent $event): void {
    $document = $event->getDocument();
    $uri = $file->getFileUri();
    $document->setUrl(file_create_url($uri));
    $document->setContent(file_get_contents($uri));
    $document->setIsFile(TRUE);
    $document->setCanBeIngested(TRUE);
  }

}
