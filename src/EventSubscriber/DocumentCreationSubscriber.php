<?php

declare(strict_types = 1);

namespace Drupal\oe_search\EventSubscriber;

use Drupal\file\Entity\File;
use Drupal\oe_search\Event\DocumentCreationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to add additional parsing to documents.
 */
class DocumentCreationSubscriber implements EventSubscriberInterface {

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
      case $entity instanceof File:
        $this->setFileFields($entity, $event);
        break;

      case $entity instanceof Media:
        $fid = $entity->getSource()->getSourceFieldValue($entity);
        $file = File::load($fid);
        $this->setFileFields($file, $event);
        break;

      default:
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
