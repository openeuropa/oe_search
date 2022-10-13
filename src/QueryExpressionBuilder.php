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
   * @param bool $exclude_or
   *   Whether to exclude or conditions (used for facets).
   *
   * @return array
   *   The condition group to be used in ES.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function prepareConditionGroup(ConditionGroup $conditionGroup, Query $query, $exclude_or = FALSE) : array {
    $query_conjunction = $conditionGroup->getConjunction();
    $conditions = $negated_conditions = $return = [];
    $query_conditions = $conditionGroup->getConditions();

    if (empty($query_conjunction)) {
      return $conditions;
    }

    // We exclude conditions which are prepared for facets.
    // These conditions should not be applied in case of OR facets.
    // They are identified with the presence of the tag.
    if ($exclude_or && !empty($conditionGroup->getTags())) {
      $tags = $conditionGroup->getTags();
      foreach ($tags as $tag) {
        if (strpos($tag, 'facet:') === 0) {
          return $conditions;
        }
      }
    }

    // Loop through the conditions.
    foreach ($query_conditions as $condition) {
      if ($condition instanceof Condition) {
        if (empty($condition->getField())) {
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
        $prepared_condition = $this->prepareConditionGroup($condition, $query, $exclude_or);
        if (!empty($prepared_condition)) {
          $conditions[] = $prepared_condition;
        }
      }
    }

    // We don't need a condition group for a single condition.
    if (count($conditions) == 1) {
      return $conditions[0];
    }

    // Both conditions are empty.
    if (empty($conditions) && empty($negated_conditions)) {
      return [];
    }

    // Support for negated conditions (must_not).
    if (!empty($negated_conditions)) {
      $query_conjunctions['must_not'] = $negated_conditions;
    }

    // Support for AND, OR.
    if (!empty($conditions)) {
      $conjuction = ($query_conjunction == 'AND') ? 'must' : 'should';
      $query_conjunctions[$conjuction] = $conditions;
    }

    return [
      'bool' => $query_conjunctions,
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
    elseif ($condition->getOperator() == 'BETWEEN') {
      return [
        'range' => [
          $field => [
            'gte' => $condition->getValue()[0],
            'lte' => $condition->getValue()[1],
          ],
        ],
      ];
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
