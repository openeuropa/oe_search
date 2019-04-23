<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\locale\Gettext;

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

    foreach (file_scan_directory(drupal_get_path('module', 'oe_search') . '/translations', '/.*\.po/') as $file) {
      $file->langcode = $file->name;
      Gettext::fileToDatabase($file, [
        'overwrite_options' => [
          'not_customized' => TRUE,
        ],
      ]);
    }
  }

  /**
   * Install locale core module.
   *
   * @BeforeScenario @locale
   */
  public function installLocale() {
    if (!\Drupal::moduleHandler()->moduleExists('locale')) {
      \Drupal::service('module_installer')->install(['locale']);
    }
  }

  /**
   * Uninstall locale core module.
   *
   * @AfterScenario @locale
   */
  public function uninstallLocale() {
    if (\Drupal::moduleHandler()->moduleExists('locale')) {
      \Drupal::service('module_installer')->uninstall(['locale']);
    }
  }

}
