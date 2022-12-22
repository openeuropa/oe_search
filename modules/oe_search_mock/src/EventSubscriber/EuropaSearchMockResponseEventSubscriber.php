<?php

declare(strict_types = 1);

namespace Drupal\oe_search_mock\EventSubscriber;

use Drupal\oe_search_mock\Config\EuropaSearchMockServerConfigOverrider;
use Drupal\oe_search_mock\EuropaSearchFixturesGenerator;
use Drupal\oe_search_mock\EuropaSearchMockResponseEvent;
use Drupal\oe_search_mock\EuropaSearchMockTrait;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to Drupal\oe_search_mock\EuropaSearchMockResponseEvent event.
 */
class EuropaSearchMockResponseEventSubscriber implements EventSubscriberInterface {

  use EuropaSearchMockTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [EuropaSearchMockResponseEvent::EUROPA_SEARCH_MOCK_RESPONSE_EVENT => 'searchResponse'];
  }

  /**
   * Sets json responses for search.
   *
   * @param \Drupal\oe_search_mock\EuropaSearchMockResponseEvent $event
   *   The event.
   */
  public function searchResponse(EuropaSearchMockResponseEvent $event): void {
    $request = $event->getRequest();
    $path = $request->getUri()->getPath();

    // We are only interested in search responses.
    $covered_paths = [
      EuropaSearchMockServerConfigOverrider::ENDPOINT_SEARCH,
      EuropaSearchMockServerConfigOverrider::ENDPOINT_FACET,
    ];
    if (!in_array($path, $covered_paths)) {
      return;
    }

    $filters = $this->getFiltersFromRequest($request);

    switch ($path) {
      case EuropaSearchMockServerConfigOverrider::ENDPOINT_SEARCH:
        $json = EuropaSearchFixturesGenerator::getSearchJson($filters);
        break;

      case EuropaSearchMockServerConfigOverrider::ENDPOINT_FACET:
        $json = EuropaSearchFixturesGenerator::getFacetsJson($filters);
        break;
    }

    $response = new Response(200, [], $json);
    $event->setResponse($response);
  }

}
