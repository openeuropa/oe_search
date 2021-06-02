<?php

declare(strict_types = 1);

namespace Drupal\oe_search\EventSubscriber;

use Drupal\Core\Entity\EntityPublishedInterface;
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
    $document = $event->getDocument();

    // @todo: media & files will override the document url, content.
    if ($entity instanceof EntityPublishedInterface) {
      $document->setStatus($entity->isPublished());
    }
  }

}
