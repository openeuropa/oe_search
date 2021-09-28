<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the search block.
 */
class SearchBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'language',
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

    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Tests the block itself.
   */
  public function testBlock(): void {
    $assert_session = $this->assertSession();

    // A user without the "access content" permission doesn't have access to the
    // block.
    $this->drupalLogin($this->createUser());
    $this->drupalGet('<front>');
    $assert_session->elementNotExists('css', '#block-oe-search');

    $this->drupalLogin($this->createUser(['access content']));
    $block = $assert_session->elementExists('css', '#block-oe-search');

    // Disable redirects to avoid loading web pages outside the test
    // environment.
    $this->getSession()->getDriver()->getClient()->followRedirects(FALSE);

    // Launch a search.
    $block->fillField('Search', 'European Commission');
    $block->pressButton('Search');
    // Verify that the user would be redirected to the ec.europa.eu search page
    // with English as language.
    $headers = $this->getSession()->getResponseHeaders();
    $this->assertNotEmpty($headers['Location']);
    $this->assertEquals('https://ec.europa.eu/search/?QueryText=European%20Commission&swlang=en', $headers['Location'][0]);

    // Test that the correct language is passed to the search redirect url.
    $this->getSession()->getDriver()->getClient()->followRedirects(TRUE);
    $this->drupalGet('/fr');
    $block = $assert_session->elementExists('css', '#block-oe-search');
    $block->fillField('Search', 'European Commission');
    $this->getSession()->getDriver()->getClient()->followRedirects(FALSE);
    $block->pressButton('Search');
    $headers = $this->getSession()->getResponseHeaders();
    $this->assertNotEmpty($headers['Location']);
    $this->assertEquals('https://ec.europa.eu/search/?QueryText=European%20Commission&swlang=fr', $headers['Location'][0]);
  }

}
