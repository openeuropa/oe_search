<?php

namespace Drupal\oe_search\Plugin\search_api\processor;

use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Converts field names to their uppercase version.
 *
 * @SearchApiProcessor(
 *   id = "search_api_europa_search_processor_upper_keys",
 *   label = @Translation("Upper Case Field Keys"),
 *   description = @Translation("Upper case the field keys."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_index" = 0,
 *   }
 * )
 */
class SearchApiEuropaSearchUpperProcessorUpperKeys extends FieldsProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      $fields = $item->getFields();
      $uppercase_fields = [];
      foreach ($fields as $name => $field) {
        $uppercase_fields[strtoupper($name)] = $field;
      }
      $item->setFields($uppercase_fields);
    }
  }

}
