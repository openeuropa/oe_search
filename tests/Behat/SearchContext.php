<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * The main Search context.
 */
class SearchContext extends RawDrupalContext {

  /**
   * Assert redirect to expected url.
   *
   * @param string $uri
   *   Expected redirect url.
   *
   * @Then I should be redirected to :uri
   *
   * @throws \Exception
   */
  public function assertRedirect(string $uri): void {
    $current_uri = $this->getSession()->getCurrentUrl();
    if ($current_uri !== $uri) {
      throw new \Exception(sprintf('Redirect to "%s" does not expected.', $uri));
    }
  }

}
