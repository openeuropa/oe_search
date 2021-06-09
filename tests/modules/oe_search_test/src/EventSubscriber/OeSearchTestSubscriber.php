<?php

declare(strict_types = 1);

namespace Drupal\oe_search_test\EventSubscriber;

use Drupal\oe_search\Event\DocumentCreationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to \Drupal\oe_search\Event\DocumentCreationEvent event.
 */
class OeSearchTestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      DocumentCreationEvent::class => 'indexEntityTestMulRevChanged',
    ];
  }

  /**
   * Allows to index EntityTestMulRevChanged entities.
   *
   * @param \Drupal\oe_search\Event\DocumentCreationEvent $event
   *   The document creation event instance.
   */
  public function indexEntityTestMulRevChanged(DocumentCreationEvent $event): void {
    // @see \Drupal\oe_search\Tests\BackendTest::testIndexItems()
    if (!\Drupal::state()->get('oe_search_test.enable_document_alter', FALSE)) {
      return;
    }

    if ($event->getEntity()->getEntityTypeId() === 'entity_test_mulrev_changed') {
      $event->getDocument()->setCanBeIngested(TRUE);
    }
  }

}
