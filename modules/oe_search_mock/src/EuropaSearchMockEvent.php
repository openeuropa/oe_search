<?php

declare(strict_types = 1);

namespace Drupal\oe_search_mock;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to collect the mocking JSON data.
 */
class EuropaSearchMockEvent extends Event {

  /**
   * Event name.
   */
  const EUROPA_SEARCH_MOCK_EVENT = 'europa_search_mock.event';

  /**
   * The resources JSON data.
   *
   * @var array
   */
  protected $resources;

  /**
   * EuropaSearchMockEvent constructor.
   *
   * @param array $resources
   *   The resources JSON data.
   */
  public function __construct(array $resources = []) {
    $this->resources = $resources;
  }

  /**
   * Get Responses.
   *
   * @return array
   *   The resources.
   */
  public function getResources(): array {
    return $this->resources;
  }

  /**
   * Setter.
   *
   * @param array $resources
   *   The resources.
   */
  public function setResources(array $resources): void {
    $this->resources = $resources;
  }

}
