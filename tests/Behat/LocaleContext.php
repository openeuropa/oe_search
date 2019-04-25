<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * The main Locale context.
 */
class LocaleContext extends RawDrupalContext {

  /**
   * Install oe_multilingual module.
   *
   * @BeforeScenario @multilingual
   */
  public function installLocale() {
    \Drupal::service('module_installer')->install(['oe_multilingual']);
  }

  /**
   * Uninstall oe_multilingual module.
   *
   * @AfterScenario @multilingual
   */
  public function uninstallLocale() {
    \Drupal::service('module_installer')->uninstall(['oe_multilingual']);
  }

}
