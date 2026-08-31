<?php

declare(strict_types=1);

namespace Drupal\oe_search_ingestion_tracking;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\oe_search_ingestion_tracking\Entity\SearchIngestionTracking;
use Drupal\search_api\Entity\Index;

/**
 * Retrieves ingestion tracking statuses.
 */
class IngestionTrackingStatusChecker {

  // Number of items to be checked per run.
  const ITEMS_PER_RUN = 100;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new IngestionTrackingStatusChecker.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Processes unfininished ingestion statuses.
   */
  public function processUnfinishedIngestionsStatuses() {
    $status_codes = [
      'PROCESSING' => SearchIngestionTracking::STATUS_PROCESSING,
      'ERROR' => SearchIngestionTracking::STATUS_ERROR,
      'FINISHED' => SearchIngestionTracking::STATUS_FINISHED,
      'PROCESSED' => SearchIngestionTracking::STATUS_FINISHED,
      'QUEUED' => SearchIngestionTracking::STATUS_QUEUED,
      'INFO' => SearchIngestionTracking::STATUS_INFO,
    ];

    $storage = $this->entityTypeManager->getStorage('oe_search_ingestion_tracking');

    $tracking_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', SearchIngestionTracking::STATUS_PROCESSING, '=')
      ->condition('retries', 2, '<')
      ->sort('created', 'ASC')
      ->range(0, self::ITEMS_PER_RUN)
      ->execute();

    if (!$tracking_ids) {
      return;
    }

    $backends = [];
    $entities = $storage->loadMultiple($tracking_ids);
    foreach ($entities as $entity) {
      $index = Index::load($entity->get('index')->value);
      if (empty($index)) {
        continue;
      }

      $backend_id = $index->getServerInstance()->getBackend()->getPluginId();
      $backends[$backend_id]['backend'] = $index->getServerInstance()->getBackend();
      $backends[$backend_id]['entities'][] = $entity->id();
    }

    // No valid backends.
    if (empty($backends)) {
      return;
    }

    $ingestions_statuses = [];
    foreach ($backends as $backend_id => $backend_jobs) {
      $statuses = $backend_jobs['backend']->getIngestionTrackingStatuses($backend_jobs['entities']);
      if (empty($statuses)) {
        continue;
      }

      /** @var \OpenEuropa\EuropaSearchClient\Model\IngestionTracking $status */
      foreach ($statuses as $status) {
        $ingestions_statuses[$status->getTrackingId()] = $status->getStatus();
      }
    }

    foreach ($entities as $tracking_id => $entity) {
      if (!empty($ingestions_statuses[$tracking_id])) {
        $entity->set('status', $status_codes[$ingestions_statuses[$tracking_id]]);
      }
      else {
        $entity->set('retries', ((int) $entity->get('retries')->value) + 1);
      }
      $entity->set('processed', time());
      $entity->save();
    }
  }

  /**
   * Removes ingestion statuses older than a month.
   */
  public function purgeOldIngestionStatuses(): void {
    $this->database->delete('oe_search_ingestion_tracking')
      ->condition('created', strtotime('-1 month'), '<')
      ->execute();
  }

}
