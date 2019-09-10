<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Assert;

/**
 * The main Search context.
 */
class SearchContext extends RawDrupalContext {

  /**
   * Prevent redirections for the whole scenario.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The Hook scope.
   *
   * @BeforeScenario @no-redirects
   */
  public function preventRedirects(BeforeScenarioScope $scope): void {
    $this->iDoNotFollowRedirects();
  }

  /**
   * Allow redirections after th escenario.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   The Hook scope.
   *
   * @AfterScenario @no-redirects
   */
  public function allowRedirects(AfterScenarioScope $scope): void {
    $this->iFollowRedirects();
  }

  /**
   * Prevent redirects so we can check their target before they happen.
   *
   * @When /^I do not follow redirects$/
   */
  public function iDoNotFollowRedirects() {
    $this->getSession()->getDriver()->getClient()->followRedirects(FALSE);
  }

  /**
   * Allow redirects during tests (usually after we have prevented them first).
   *
   * @When /^I follow redirects$/
   */
  public function iFollowRedirects() {
    $this->getSession()->getDriver()->getClient()->followRedirects(TRUE);
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
