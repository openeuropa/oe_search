<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Plugin\facets\query_type;

use Drupal\oe_list_pages\Plugin\facets\query_type\Date;

/**
 * Extends the original query type to handle date values in milliseconds.
 *
 * @FacetsQueryType(
 *   id = "europa_date_query_type",
 *   label = @Translation("Europa date query type"),
 * )
 */
class EuropaDate extends Date {

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function execute() {
    $query = $this->query;

    $active_items = static::getActiveItems($this->facet);

    // Only alter the query when there's an actual query object to alter.
    if (empty($query) || !$active_items) {
      return;
    }

    $widget_config = $this->facet->getWidgetInstance()->getConfiguration();

    $operator = $active_items['operator'];

    $first_date = $active_items['first'];
    $second_date = $active_items['second'] ?? NULL;

    // Handle the BETWEEN case first where we have two dates to compare.
    if ($operator === 'bt' && $second_date) {
      if ($widget_config['date_type'] === parent::DATETIME_TYPE_DATE) {
        $this->adaptDatesPerOperator($operator, $first_date, $second_date);
      }

      $value = [$first_date->format('Uv'), $second_date->format('Uv')];
      $query->addCondition($this->facet->getFieldIdentifier(), $value, parent::OPERATORS[$operator]);
      return;
    }

    // Handle the single date comparison.
    if ($widget_config['date_type'] === parent::DATETIME_TYPE_DATE) {
      $this->adaptDatesPerOperator($operator, $first_date);
    }

    $query->addCondition($this->facet->getFieldIdentifier(), $first_date->format('Uv'), parent::OPERATORS[$operator]);
  }

}
