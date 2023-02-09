<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Plugin\facets\query_type;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_list_pages\Plugin\facets\query_type\DateStatus;

/**
 * Provides support for date status facets for Europa pages.
 *
 * @FacetsQueryType(
 *   id = "oe_list_pages_europa_date_status_query_type",
 *   label = @Translation("Europa date status"),
 * )
 */
class EuropaDateStatus extends DateStatus implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected function prepareTimestamp(DrupalDateTime $date): int {
    return (int) $date->format('Uv');
  }

}
