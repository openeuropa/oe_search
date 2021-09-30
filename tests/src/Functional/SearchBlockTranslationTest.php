<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the search block translations.
 */
class SearchBlockTranslationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'language',
    'oe_multilingual',
    'oe_search',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('oe_search', [
      'id' => 'oe-search',
    ]);

    // Import the translations for 3 test languages.
    \Drupal::service('oe_multilingual.local_translations_batcher')->createBatch([
      'en',
      'fr',
      'it',
    ]);
    // Force the batch to execute.
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    batch_process();
  }

  /**
   * Tests that translations are provided for the search block button.
   */
  public function testTranslations(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->createUser(['access content']));
    $this->drupalGet('<front>');
    $block = $assert_session->elementExists('css', '#block-oe-search');
    $assert_session->buttonExists('Search', $block);
    // Check that the block button is translated in French.
    $this->drupalGet('<front>', [
      'language' => \Drupal::languageManager()->getLanguage('fr'),
    ]);
    $assert_session->buttonExists('Rechercher', $block);
    // And in Italian.
    $this->drupalGet('<front>', [
      'language' => \Drupal::languageManager()->getLanguage('it'),
    ]);
    $assert_session->buttonExists('Cerca', $block);
  }

}
