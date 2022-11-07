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
    if ($path != EuropaSearchMockServerConfigOverrider::ENDPOINT_SEARCH) {
      return;
    }

    $filters = $this->getFiltersFromRequest($request);
    $json = EuropaSearchFixturesGenerator::getSearchJson($filters);
    $response = new Response(200, [], $json);
    $event->setResponse($response);
  }

}
