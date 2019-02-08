<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\Core\Site\Settings;

/**
 * The main Search context.
 */
class SearchContext extends RawDrupalContext {

  /**
   * Disables the Search test module.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   The scope.
   *
   * @afterScenario @oe_search
   */
  public function disableTestModule(AfterScenarioScope $scope): void {
    $this->enableTestModuleScanning();
    \Drupal::service('module_installer')->uninstall(['oe_search_test']);
  }

  /**
   * Enables the Search test module.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The scope.
   *
   * @beforeScenario @oe_search
   */
  public function enableTestModule(BeforeScenarioScope $scope): void {
    $this->enableTestModuleScanning();
    \Drupal::service('module_installer')->install(['oe_search_test']);
  }

  /**
   * Enables the test module scanning.
   *
   * The AV Portal media mock is a test module so it cannot be enabled by
   * default as it is not being scanned. By changing the settings temporarily,
   * we can allow that to happen.
   */
  protected function enableTestModuleScanning(): void {
    $settings = Settings::getAll();
    $settings['extension_discovery_scan_tests'] = TRUE;
    // We just have to re-instantiate the singleton.
    new Settings($settings);
  }

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
