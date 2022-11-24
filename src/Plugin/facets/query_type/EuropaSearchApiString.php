<?php

namespace Drupal\oe_search\Plugin\facets\query_type;

use Drupal\facets\Plugin\facets\query_type\SearchApiString;
use Drupal\facets\Result\Result;
use Drupal\search_api\Query\QueryInterface;

/**
 * Provides support for string facets for Europa Search.
 *
 * We extend the original query type to have better support for booleans.
 * String query type is used for boolean comparison but need to convert
 * original values to properly handle data coming from EuropaSearch as
 * "true" and "false".
 * Method code mostly copied over from parent.
 *
 * @FacetsQueryType(
 *   id = "europa_search_api_string",
 *   label = @Translation("Europa String"),
 * )
 */
class EuropaSearchApiString extends SearchApiString {

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function execute() {
    $field_identifier = $this->facet->getFieldIdentifier();
    /** @var \Drupal\search_api\Entity\Index $index */
    $index = $this->facet->getFacetSource()->getIndex();
    $field = $index->getField($field_identifier);

    // We only want to change the query for booleans.
    if ($field->getType() !== 'boolean') {
      parent::execute();
      return;
    }
    $active_items = $this->facet->getActiveItems();
    $query = $this->query;

    // Only alter the query when there's an actual query object to alter.
    if (!empty($query)) {
      $operator = $this->facet->getQueryOperator();
      $exclude = $this->facet->getExclude();

      if ($query->getProcessingLevel() === QueryInterface::PROCESSING_FULL) {
        // Set the options for the actual query.
        $options = &$query->getOptions();
        $options['search_api_facets'][$field_identifier] = $this->getFacetOptions();
      }

      if (count($active_items)) {
        $filter = $query->createConditionGroup($operator, ['facet:' . $field_identifier]);
        foreach ($active_items as $value) {
          if (str_starts_with($value, '!(')) {
            /** @var \Drupal\facets\UrlProcessor\UrlProcessorInterface $urlProcessor */
            $urlProcessor = $this->facet->getProcessors()['url_processor_handler']->getProcessor();
            foreach (explode($urlProcessor->getDelimiter(), substr($value, 2, -1)) as $missing_value) {
              // Note that $exclude needs to be inverted for "missing".
              $missing_value = ($missing_value === '0') ? "false" : "true";
              $filter->addCondition($this->facet->getFieldIdentifier(), $missing_value, !$exclude ? '<>' : '=');
            }
          }
          else {
            $value = ($value === '0') ? "false" : "true";
            $filter->addCondition($this->facet->getFieldIdentifier(), $value, $exclude ? '<>' : '=');
          }
        }
        $query->addConditionGroup($filter);
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function build() {
    $field_identifier = $this->facet->getFieldIdentifier();
    /** @var \Drupal\search_api\Entity\Index $index */
    $index = $this->facet->getFacetSource()->getIndex();
    $field = $index->getField($field_identifier);

    // We only want to change the results for booleans.
    if ($field->getType() !== 'boolean') {
      parent::build();
      return $this->facet;
    }

    // For booleans we need to convert "true", "false" to "0", "1" in case the
    // value comes from the ES index. Otherwise, it can also still be "0" or "1"
    // if the face is being rebuilt.
    $query_operator = $this->facet->getQueryOperator();
    if (!empty($this->results)) {
      $facet_results = [];
      foreach ($this->results as $result) {
        if ($result['count'] || $query_operator === 'or') {
          $result_filter = $result['filter'] ?? '';
          if ($result_filter[0] === '"') {
            $result_filter = substr($result_filter, 1);
          }
          if ($result_filter[strlen($result_filter) - 1] === '"') {
            $result_filter = substr($result_filter, 0, -1);
          }
          $count = $result['count'];

          if ($result_filter === 'true') {
            $result_filter = "1";
          }
          if ($result_filter === 'false') {
            $result_filter = "0";
          }
          $result = new Result($this->facet, $result_filter, $result_filter, $count);
          $result->setMissing($this->facet->isMissing() && $result_filter === '!');
          $facet_results[$result_filter] = $result;
        }
      }

      if (isset($facet_results['!']) && $facet_results['!']->isMissing()) {
        $facet_results['!']->setMissingFilters(array_keys($facet_results));
      }

      $this->facet->setResults(array_values($facet_results));
    }

    return $this->facet;
  }

}
