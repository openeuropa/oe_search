<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_search\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
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
    'node_storage_body_field',
    'user',
    'media',
    'text',
    'image',
    'file',
    'views',
    'http_request_mock',
    'oe_search',
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

    // The "europa_nodes" search index shipped by oe_search_demo indexes the
    // node "body" field. Recent Drupal/search_api versions resolve the data
    // definition of every indexed field when the index/view configuration is
    // installed, so a node bundle exposing a "body" field must exist before
    // oe_search_demo is installed. The "body" field storage is provided by the
    // node_storage_body_field module (Drupal core 11.3+, contrib backport on
    // older versions).
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    FieldConfig::create([
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Body',
    ])->save();
    \Drupal::service('module_installer')->install(['oe_search_demo']);

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
