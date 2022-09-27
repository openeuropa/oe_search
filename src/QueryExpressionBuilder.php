<?php

declare(strict_types = 1);

namespace Drupal\oe_search;

use Drupal\search_api\Query\Condition;
use Drupal\search_api\Query\ConditionGroup;
use Drupal\search_api\Query\Query;

/**
 * Service that prepares Europa Search Query Expressions.
 */
class QueryExpressionBuilder {

  /**
   * Prepares a query expression for a condition group.
   *
   * @param \Drupal\search_api\Query\ConditionGroup $conditionGroup
   *   The condition group.
   * @param \Drupal\search_api\Query\Query $query
   *   The original query.
   *
   * @return array
   *   The condition group to be used in ES.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function prepareConditionGroup(ConditionGroup $conditionGroup, Query $query) : array {
    $query_conjunction = $conditionGroup->getConjunction();
    $conditions = $negated_conditions = $return = [];
    $query_conditions = $conditionGroup->getConditions();

    if (empty($query_conjunction)) {
      return $conditions;
    }

    // Loop through the conditions.
    foreach ($query_conditions as $condition) {
      if ($condition instanceof Condition) {
        if (empty($condition->getValue())) {
          continue;
        }
        $europa_condition = $this->prepareCondition($condition, $query);
        // This is a negated condition.
        if (key($europa_condition) == 'must_not') {
          $negated_conditions[] = reset($europa_condition);
        }
        else {
          $conditions[] = $europa_condition;
        }
      }
      // Recursively handle condition.
      elseif ($condition instanceof ConditionGroup) {
        $conditions[] = $this->prepareConditionGroup($condition, $query);
      }
    }

    // We don't need a condition group for a single condition.
    if (count($conditions) == 1) {
      return $conditions[0];
    }

    // Support for negated conditions (must_not).
    if (!empty($negated_conditions)) {
      return [
        'bool' => [
          'must_not' => $negated_conditions,
        ],
      ];
    }

    // Support for AND, OR.
    $conjuction = ($query_conjunction == 'AND') ? 'must' : 'should';
    return [
      'bool' => [
        $conjuction => $conditions,
      ],
    ];
  }

  /**
   * Adds condition to the condition group.
   *
   * @param \Drupal\search_api\Query\Condition $condition
   *   The query condition.
   * @param \Drupal\search_api\Query\Query $query
   *   The original query.
   *
   * @return array
   *   The array with resulting condition.
   */
  protected function prepareCondition(Condition $condition, Query $query) {
    $field = Utility::getEsFieldName($condition->getField(), $query);
    if ($condition->getOperator() == '>') {
      return ['range' => [$field => ['gt' => $condition->getValue()]]];
    }
    elseif ($condition->getOperator() == '>=') {
      return ['range' => [$field => ['gte' => $condition->getValue()]]];
    }
    elseif ($condition->getOperator() == '<') {
      return ['range' => [$field => ['lt' => $condition->getValue()]]];
    }
    elseif ($condition->getOperator() == '<=') {
      return ['range' => [$field => ['lte' => $condition->getValue()]]];
    }
    elseif ($condition->getOperator() == 'IN') {
      return ['terms' => [$field => array_values($condition->getValue())]];
    }
    elseif ($condition->getOperator() == '<>') {
      return ['must_not' => ['term' => [$field => $condition->getValue()]]];
    }
    else {
      return ['term' => [$field => $condition->getValue()]];
    }
  }

}
