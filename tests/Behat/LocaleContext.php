<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * The main Search context.
 */
class LocaleContext extends RawDrupalContext {

  /**
   * Imports the translation files from the module.
   *
   * @Given I import all translations
   */
  public function importTranslations(): void {
    if (!\Drupal::moduleHandler()->moduleExists('locale')) {
      throw new \Exception('Locale module is not enabled.');
    }

    _oe_search_translations();
  }

  /**
   * Install locale core module.
   *
   * @BeforeScenario @locale
   */
  public function installLocale() {
    \Drupal::service('module_installer')->install(['locale']);
  }

  /**
   * Uninstall locale core module.
   *
   * @AfterScenario @locale
   */
  public function uninstallLocale() {
    \Drupal::service('module_installer')->uninstall(['locale']);
  }

}
