<?php

declare(strict_types = 1);

namespace Drupal\oe_search\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;
use Drupal\oe_search\Event\EuropaEntityCreationEvent;
use Drupal\oe_search\Utility;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for ES Entity Mapping.
 */
class EuropaEntityCreationSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EuropaEntityCreationSubscriber object.
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
      EuropaEntityCreationEvent::EUROPA_ENTITY_CREATED => 'map',
    ];
  }

  /**
   * Subscribes to the metadata mapping creation event.
   *
   * @param \Drupal\oe_search\Event\EuropaEntityCreationEvent $event
   *   The event object.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function map(EuropaEntityCreationEvent $event): void {
    $metadata = $event->getMetadata();
    $entity = $event->getEntity();
    $query = $event->getQuery();
    $datasource_id = $metadata[Utility::getEsFieldName('search_api_datasource', $query)][0];

    $index_fields = $query->getIndex()->getFieldsByDatasource($datasource_id);

    foreach ($index_fields as $field) {
      $metadata_key = Utility::getEsFieldName($field->getFieldIdentifier(), $query);
      $original_field_id = $field->getOriginalFieldIdentifier();
      $data_definition = $field->getDataDefinition();

      if (!$data_definition instanceof FieldItemDataDefinitionInterface) {
        continue;
      }

      // We only alter values present in metadata.
      if (empty($metadata[$metadata_key][0])) {
        continue;
      }

      if (!$entity->hasField($original_field_id)) {
        continue;
      }

      // Support for booleans.
      if ($field->getType() === 'boolean') {
        $entity->set($original_field_id, filter_var($entity->get($original_field_id)->value, FILTER_VALIDATE_BOOLEAN));
      }
      elseif ($field->getType() === 'date') {
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.vP', $metadata[$metadata_key][0]);

        $date_type = $field->getDataDefinition()
          ->getFieldDefinition()
          ->getType();
        if ($date_type == 'datetime') {
          $datetime_type = $field->getDataDefinition()
            ->getSettings()['datetime_type'];
        }

        // Date time fields with date only.
        if ($date_type == 'datetime' && $datetime_type == 'date') {
          $entity->set($original_field_id, date('Y-m-d', $date->getTimestamp()));
        }
        // Date time fields with date and time.
        elseif ($date_type == 'datetime' && $datetime_type == 'datetime') {
          $entity->set($original_field_id, date('Y-m-d\TH:i:s', $date->getTimestamp()));
        }
        elseif ($date_type == 'daterange_timezone') {
          $entity->set($field->getOriginalFieldIdentifier(), [
            'value' => date('Y-m-d\TH:i:s', $date->getTimestamp()),
            'end_value' => date('Y-m-d\TH:i:s', $date->getTimestamp()),
            'timezone' => $date->getTimezone()->getName(),
          ]);
        }
        else {
          $entity->set($original_field_id, $date->getTimestamp());
        }
      }
    }
  }

}
