<?php

declare(strict_types=1);

namespace Drupal\oe_search;

use OpenEuropa\EuropaSearchClient\Endpoint\TokenEndpoint;
use OpenEuropa\EuropaSearchClient\Model\Token;

/**
 * Service that extends token endpoint caching the resulting token.
 */
class CacheableTokenEndpoint extends TokenEndpoint {

  /**
   * {@inheritDoc}
   */
  public function execute(): Token {
    $cache_service = \Drupal::cache('default');
    $cached_token = $cache_service->get('oe_search_auth_cached');
    if (!empty($cached_token)) {
      return $cached_token->data;
    }

    $token = parent::execute();
    // Cache the token for the valid time.
    $cache_service->set('oe_search_auth_cached', $token, time() + $token->getExpiresIn());
    return $token;
  }

}
