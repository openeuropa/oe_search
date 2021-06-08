<?php

declare(strict_types = 1);

namespace Drupal\oe_search;

/**
 * Utility methods.
 */
class Utility {

  /**
   * Returns a unique hash for the current site.
   *
   * This is used to identify the Europa Search documents from different sites
   * within a single Europa Search database.
   *
   * @return string
   *   A unique site hash, containing only alphanumeric characters.
   */
  public static function getSiteHash(): string {
    $state = \Drupal::state();
    if (!$hash = $state->get('oe_search.site_hash')) {
      global $base_url;
      $hash = substr(base_convert(hash('sha256', uniqid($base_url, TRUE)), 16, 36), 0, 6);
      $state->set('oe_search.site_hash', $hash);
    }
    return $hash;
  }

}
