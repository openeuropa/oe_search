<?php

declare(strict_types = 1);

namespace Drupal\oe_search;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\oe_search\Event\EuropaEntityCreationEvent;
use Drupal\oe_search\Event\MetadataMappingEvent;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\search_api\Query\QueryInterface;

/**
 * Service that prepares a Drupal Entity from ES document.
 */
class EntityMapper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventService;

  /**
   * Constructs an EntityMapper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_service
   *   The event service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContainerAwareEventDispatcher $event_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventService = $event_service;
  }

  /**
   * Returns an entity adapter from mapped values.
   *
   * @param array $metadata
   *   The metadata array.
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query.
   *
   * @return null|\Drupal\Core\Entity\Plugin\DataType\EntityAdapter
   *   The mapped entity.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function map(array $metadata, QueryInterface $query) : ?EntityAdapter {
    $mapped_entity = NULL;
    $datasource_id = $metadata[Utility::getEsFieldName('search_api_datasource', $query)][0];
    $index_fields = $query->getIndex()->getFieldsByDatasource($datasource_id);
    $datasource = $query->getIndex()->getDatasource($datasource_id);

    // We only support entities from ContentEntity datasource.
    if (!$datasource instanceof ContentEntity) {
      return $mapped_entity;
    }

    $entity_type_id = $datasource->getDerivativeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_id_key = $entity_type->getKey('id');
    $entity_values = [];
    foreach ($index_fields as $field) {
      $metadata_key = Utility::getEsFieldName($field->getFieldIdentifier(), $query);

      // We don't have the original field.
      if (!$field->getDataDefinition() instanceof FieldItemDataDefinition) {
        continue;
      }

      // By default set the direct mapping.
      if (!empty($metadata[$metadata_key][0])) {
        $entity_values[$field->getOriginalFieldIdentifier()] = $metadata[$metadata_key][0];
      }

      $event = new MetadataMappingEvent($query, $metadata, $metadata_key, $field, $entity_values);
      $this->eventService->dispatch($event);
      $entity_values = $event->getValues();
    }

    // We want to be able to call getUrl() on the entity, so we set a fake id.
    $entity_values[$entity_id_key] = PHP_INT_MAX;

    // Create entity from array of values.
    try {
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($entity_values);
      // Needed to avoid loading entity in translations.
      $entity->in_preview = TRUE;
      // Allow event subscribers to alter the created entity.
      $event = new EuropaEntityCreationEvent($entity, $metadata, $query);
      $this->eventService->dispatch($event);
      $mapped_entity = EntityAdapter::createFromEntity($entity);
    }
    catch (EntityStorageException $e) {
      // Don't do anything.
    }
    return $mapped_entity;
  }

}
