<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_search\Functional;

use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the language filter.
 */
class LanguageFilterViewTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'search_api',
    'system',
    'datetime',
    'locale',
    'language',
    'node',
    'user',
    'media',
    'text',
    'image',
    'file',
    'views',
    'http_request_mock',
    'oe_search',
    'oe_search_demo',
    'oe_search_mock',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Save index to make sure view is correct.
    Index::load('europa_nodes')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    // Rebuild container to make sure that the language path processor is
    // picked up.
    $this->rebuildContainer();
  }

  /**
   * Tests the multilingual filters.
   */
  public function testMultilingualView(): void {
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    // First in default language.
    $this->drupalGet('/europa-demo-view');
    $this->assertSession()->pageTextContains('Displaying 1 - 5 of 5');

    // Now in french.
    $this->drupalGet('/europa-demo-view', [
      'language' => \Drupal::languageManager()->getLanguage('fr'),
    ]);
    $this->assertSession()->pageTextContains('Displaying 1 - 2 of 2');
  }

}
