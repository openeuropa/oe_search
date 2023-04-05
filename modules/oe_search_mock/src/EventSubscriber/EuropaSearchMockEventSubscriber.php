<?php

declare(strict_types = 1);

namespace Drupal\oe_search_mock\EventSubscriber;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\oe_search_mock\EuropaSearchMockEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to Drupal\oe_search_mock\EuropaSearchMockEvent event.
 */
class EuropaSearchMockEventSubscriber implements EventSubscriberInterface {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * Instantiates the class.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(ModuleExtensionList $moduleExtensionList) {
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [EuropaSearchMockEvent::EUROPA_SEARCH_MOCK_EVENT => 'setMockResources'];
  }

  /**
   * Sets json responses for mocked Europa Search server.
   *
   * @param \Drupal\oe_search_mock\EuropaSearchMockEvent $event
   *   The event.
   */
  public function setMockResources(EuropaSearchMockEvent $event): void {
    $resources = $event->getResources();
    $responses_json = [
      'delete_document_response',
      'info_response',
      'jwt_response',
      'simple_search_response',
      'simple_search_response_page_1',
      'simple_search_response_page_2',
      'simple_search_response_page_3',
      'text_ingestion_response',
      'facets_response',
    ];
    foreach ($responses_json as $response_name) {
      $resources[$response_name] = file_get_contents($this->moduleExtensionList->getPath('oe_search_mock') . '/responses/json/' . $response_name . '.json');
    }

    $event->setResources($resources);
  }

}
