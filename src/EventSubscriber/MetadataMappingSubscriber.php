<?php

declare(strict_types = 1);

namespace Drupal\oe_search\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\oe_search\Event\MetadataMappingEvent;
use Drupal\oe_search\Utility;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for ES Entity Mapping.
 */
class MetadataMappingSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MetadataMappingSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MetadataMappingEvent::class => 'map',
    ];
  }

  /**
   * Subscribes to the metadata mapping creation event.
   *
   * @param \Drupal\oe_search\Event\MetadataMappingEvent $event
   *   The event object.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function map(MetadataMappingEvent $event): void {
    $field = $event->getField();
    $metadata = $event->getMetadata();
    $metadata_key = $event->getMetadataKey();
    $values = $event->getValues();
    $query = $event->getQuery();
    $original_field_id = $field->getOriginalFieldIdentifier();
    $original_field_type = $field->getDataDefinition()->getFieldDefinition()->getType();
    $datasource = $event->getQuery()->getIndex()->getDatasource($metadata['SEARCH_API_DATASOURCE'][0]);
    $entity_type_id = $datasource->getDerivativeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_bundle_key = $entity_type->getKey('bundle');

    // We only map here values present in metadata.
    if (empty($values[$original_field_id])) {
      return;
    }

    // Drop entity references, unless they are the bundle key.
    $entity_reference_types = [
      'entity_reference',
      'entity_reference_revisions',
    ];

    if ($metadata_key != Utility::getEsFieldName($entity_bundle_key, $query) && in_array($original_field_type, $entity_reference_types)) {
      unset($values[$original_field_id]);
    }

    // Support for booleans.
    if ($field->getType() == 'boolean') {
      $values[$original_field_id] = filter_var($values[$original_field_id], FILTER_VALIDATE_BOOLEAN);
    }
    elseif ($field->getType() == 'date') {
      $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.vp', $metadata[$metadata_key][0]);

      $date_type = $field->getDataDefinition()->getFieldDefinition()->getType();
      if ($date_type == 'datetime') {
        $datetime_type = $field->getDataDefinition()->getSettings()['datetime_type'];
      }

      // Date time fields with date only.
      if ($date_type == 'datetime' && $datetime_type == 'date') {
        $values[$original_field_id] = date('Y-m-d', $date->getTimestamp());
      }
      // Date time fields with date and time.
      elseif ($date_type == 'datetime' && $datetime_type == 'datetime') {
        $values[$original_field_id] = date('Y-m-d\TH:i:s', $date->getTimestamp());
      }
      elseif ($date_type == 'daterange_timezone') {
        $values[$field->getOriginalFieldIdentifier()] = [
          'value' => date('Y-m-d\TH:i:s', $date->getTimestamp()),
          'end_value' => date('Y-m-d\TH:i:s', $date->getTimestamp()),
          'timezone' => $date->getTimezone()->getName(),
        ];
      }
      else {
        $values[$original_field_id] = $date->getTimestamp();
      }
    }

    $event->setValues($values);
  }

}
