<?php

declare(strict_types=1);

namespace Drupal\oe_search\Event;

use OpenEuropa\EuropaSearchClient\Model\Ingestion;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for reacting to europa items indexed.
 *
 * We use a specific event instead of search api event
 * to guarantee we can also access the ingestion tracking id.
 */
class EuropaItemsIndexedEvent extends Event {

  /**
   * The name of the event dispatched when a new europa entity is indexed.
    */
  const EUROPA_ITEMS_INDEXED = 'oe_search.europa_items_indexed';

  /**
   * The ingestion.
   *
   * @var \OpenEuropa\EuropaSearchClient\Model\Ingestion
   */
  protected $ingestion;

  /**
   * Get the event ingestion.
   *
   * @return \OpenEuropa\EuropaSearchClient\Model\Ingestion
   *   The ingestion object.
   */
  public function getIngestion(): Ingestion {
    return $this->ingestion;
  }

  /**
   * Sets the event ingestion.
   *
   * @param \OpenEuropa\EuropaSearchClient\Model\Ingestion $ingestion
   *   The ingestion.
   */
  public function setIngestion(Ingestion $ingestion): void {
    $this->ingestion = $ingestion;
  }

}
