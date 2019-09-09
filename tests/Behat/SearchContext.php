<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Assert;

/**
 * The main Search context.
 */
class SearchContext extends RawDrupalContext {

  /**
   * Prevent redirects so we can check their target before they happen.
   *
   * @When /^I do not follow redirects$/
   */
  public function iDoNotFollowRedirects() {
    $this->getSession()->getDriver()->getClient()->followRedirects(FALSE);
  }

  /**
   * Assert redirect to expected url.
   *
   * @param string $expectedUrl
   *   The expected url.
   *
   * @Then /^I (?:am|should be) redirected to "([^"]*)"$/
   */
  public function iAmRedirectedTo($expectedUrl) {
    $headers = $this->getSession()->getResponseHeaders();
    Assert::assertTrue(isset($headers['Location'][0]));
    Assert::assertEquals($expectedUrl, $headers['Location'][0]);
  }

}
