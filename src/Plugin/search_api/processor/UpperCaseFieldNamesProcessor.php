<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Plugin\search_api\processor;

use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Converts field names to their uppercase version.
 *
 * @SearchApiProcessor(
 *   id = "search_api_europa_search_uppercase_field_names",
 *   label = @Translation("Upper case field names"),
 *   description = @Translation("Convert the field names to their upercase version."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_index" = 0,
 *   }
 * )
 */
class UpperCaseFieldNamesProcessor extends FieldsProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items): void {
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
