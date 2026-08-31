<?php

declare(strict_types=1);

namespace Drupal\oe_search_ingestion_tracking\EventSubscriber;

use Drupal\oe_search\Event\EuropaItemsIndexedEvent;
use Drupal\oe_search_ingestion_tracking\Entity\SearchIngestionTracking;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to subscribe to indexed documents.
 */
class EuropaItemsIndexedTrackingSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      EuropaItemsIndexedEvent::EUROPA_ITEMS_INDEXED => 'trackIngestion',
    ];
  }

  /**
   * Subscribes to EuropaItemsIndexedEvent event.
   *
   * @param \Drupal\oe_search\Event\EuropaItemsIndexedEvent $event
   *   The event object.
   */
  public function trackIngestion(EuropaItemsIndexedEvent $event) {
    $item_id = $event->getIngestion()->getReference();
    if (preg_match('/^([a-z0-9]+)-([a-z_]+)-entity:([a-z_]+)\/(\d+):([a-z]{2,3}(?:-[a-z]{2,})?)$/i', $item_id, $matches)) {
      $search_ingestion_tracking = SearchIngestionTracking::create([
        'tracking_id' => $event->getIngestion()
          ->getTrackingId(),
        'index' => $event->getIndex()->id(),
        'entity_type' => $matches[3],
        'entity_id' => $matches[4],
        'langcode' => $matches[5],
      ]);
      $search_ingestion_tracking->save();
    }
  }

}
