<?php

declare(strict_types=1);

namespace Drupal\oe_search_ingestion_tracking\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the OE Search Ingestion tracking entity.
 *
 * @ContentEntityType(
 *   id = "oe_search_ingestion_tracking",
 *   label = @Translation("Search ingestion tracking"),
 *   label_collection = @Translation("Search ingestion tracking records"),
 *   label_singular = @Translation("search ingestion tracking record"),
 *   label_plural = @Translation("search ingestion tracking records"),
 *   label_count = @PluralTranslation(
 *     singular = "@count search ingestion tracking record",
 *     plural = "@count search ingestion tracking records",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "oe_search_ingestion_tracking",
 *   admin_permission = "administer search ingestion tracking",
 *   entity_keys = {
 *     "id" = "tracking_id",
 *     "label" = "tracking_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/oe-search-ingestion-tracking",
 *   },
 * )
 */
class SearchIngestionTracking extends ContentEntityBase implements ContentEntityInterface {

  /**
   * Status: Ingestion processed yet.
   */
  const STATUS_PROCESSING = 0;

  /**
   * Status: ingestion with error.
   */
  const STATUS_ERROR = 1;

  /**
   * Status: ingestion finished.
   */
  const STATUS_FINISHED = 2;

  /**
   * Status: ingestion queued.
   */
  const STATUS_QUEUED = 3;

  /**
   * Status: ingestion info.
   */
  const STATUS_INFO = 4;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tracking_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tracking ID'))
      ->setDescription(t('Unique identifier for the ingestion record.'))
      ->setRequired(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type of the ingested entity.'))
      ->setRequired(TRUE);

    $fields['index'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Index'))
      ->setDescription(t('The index that for the ingested entity.'))
      ->setRequired(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the ingested entity.'))
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the tracking record was created.'));

    $fields['processed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Processed'))
      ->setDescription(t('The time the status of the ingestion was last checked.'));

    $fields['status'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Status'))
      ->setDescription(t('The ingestion status.'))
      ->setSetting('allowed_values', [
        self::STATUS_PROCESSING => t('Processing'),
        self::STATUS_ERROR => t('Error'),
        self::STATUS_FINISHED => t('Finished'),
        self::STATUS_QUEUED => t('Queued'),
        self::STATUS_INFO => t('Info'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDefaultValue(self::STATUS_PROCESSING);

    $fields['retries'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Retries'))
      ->setDescription(t('Number of times ingestion status check has been retried.'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE);

    return $fields;
  }

}
