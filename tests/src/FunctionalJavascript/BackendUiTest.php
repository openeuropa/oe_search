<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the backend admin UI.
 *
 * @group oe_search
 */
class BackendUiTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_search',
  ];

  /**
   * @covers \Drupal\oe_search\Plugin\search_api\backend\SearchApiEuropaSearchBackend::buildConfigurationForm()
   */
  public function testBackendUi(): void {
    $admin_user = $this->drupalCreateUser([
      'administer search_api',
      'access content',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/search/search-api/add-server');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Assert backend configuration form initial state.
    $api_key = $assert_session->fieldExists('API key');
    $this->assertTrue($api_key->hasAttribute('required'));
    $database = $assert_session->fieldExists('Database');
    $this->assertTrue($database->hasAttribute('required'));
    $info = $assert_session->fieldExists('Info API endpoint');
    $this->assertTrue($info->hasAttribute('required'));
    $search = $assert_session->fieldExists('Search API endpoint');
    $this->assertTrue($search->hasAttribute('required'));
    $facet = $assert_session->fieldExists('Facets API endpoint');
    $assert_session->checkboxChecked('Enable ingestion');
    $token = $assert_session->fieldExists('Token API endpoint');
    $this->assertTrue($token->hasAttribute('required'));
    $text_ingestion = $assert_session->fieldExists('Text ingestion API endpoint');
    $this->assertTrue($text_ingestion->hasAttribute('required'));
    $file_ingestion = $assert_session->fieldExists('File ingestion API endpoint');
    $this->assertTrue($file_ingestion->hasAttribute('required'));
    $delete = $assert_session->fieldExists('Delete API endpoint');
    $this->assertTrue($delete->hasAttribute('required'));
    $missing_settings = $assert_session->elementExists('css', '#edit-backend-config-ingestion-settings');
    $assert_session->elementTextContains('css', '#edit-backend-config-ingestion-settings', 'Missing settings.php entries:');
    $assert_session->elementTextContains('css', '#edit-backend-config-ingestion-settings', '$settings[\'oe_search\'][\'server\'][\'your_server_machine_name\'][\'consumer_key\'] = \'consumer key value...\';');
    $assert_session->elementTextContains('css', '#edit-backend-config-ingestion-settings', '$settings[\'oe_search\'][\'server\'][\'your_server_machine_name\'][\'consumer_secret\'] = \'consumer secret value...\';');
    $this->assertTrue($missing_settings->isVisible());

    // Toggle enable ingestion.
    $page->uncheckField('Enable ingestion');
    $assert_session->checkboxNotChecked('Enable ingestion');
    $this->assertFalse($missing_settings->isVisible());
    $this->assertFalse($token->hasAttribute('required'));
    $this->assertFalse($text_ingestion->hasAttribute('required'));
    $this->assertFalse($file_ingestion->hasAttribute('required'));
    $this->assertFalse($delete->hasAttribute('required'));
    $this->assertTrue($token->hasAttribute('disabled'));
    $this->assertTrue($text_ingestion->hasAttribute('disabled'));
    $this->assertTrue($file_ingestion->hasAttribute('disabled'));
    $this->assertTrue($delete->hasAttribute('disabled'));
    $page->checkField('Enable ingestion');
    $assert_session->checkboxChecked('Enable ingestion');
    $this->assertTrue($missing_settings->isVisible());
    $this->assertTrue($token->hasAttribute('required'));
    $this->assertTrue($text_ingestion->hasAttribute('required'));
    $this->assertTrue($file_ingestion->hasAttribute('required'));
    $this->assertTrue($delete->hasAttribute('required'));
    $this->assertFalse($token->hasAttribute('disabled'));
    $this->assertFalse($text_ingestion->hasAttribute('disabled'));
    $this->assertFalse($file_ingestion->hasAttribute('disabled'));
    $this->assertFalse($delete->hasAttribute('disabled'));

    // Fill in fields and assert success.
    $page->fillField('Server name', 'Europa search server');
    $assert_session->waitForElementVisible('css', '#edit-id');
    $api_key->setValue('api-key');
    $database->setValue('db');
    $info->setValue('http://example.com/search/info');
    $search->setValue('http://example.com/search/search');
    $facet->setValue('http://example.com/search/facet');
    $token->setValue('http://example.com/token');
    $text_ingestion->setValue('http://example.com/ingest/text');
    $file_ingestion->setValue('http://example.com/ingest/file');
    $delete->setValue('http://example.com/ingest/delete');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The server was successfully saved.');

    // Assert that once consumer config is set warnings disappear.
    $server_id = 'europa_search_server';
    $settings = [];
    $settings['settings']['oe_search']['server'][$server_id]['consumer_key'] = (object) [
      'value' => 'consumer key value',
      'required' => TRUE,
    ];
    $settings['settings']['oe_search']['server'][$server_id]['consumer_secret'] = (object) [
      'value' => 'consumer secret value',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet("admin/config/search/search-api/server/$server_id/edit");
    $assert_session->checkboxChecked('Enable ingestion');
    $assert_session->elementNotExists('css', '#edit-backend-config-ingestion-settings');
  }

  /**
   * @covers oe_search_form_search_api_index_form_alter().
   */
  public function testIndexConfiguration() {
    $admin_user = $this->drupalCreateUser([
      'administer search_api',
      'access content',
    ]);
    $this->drupalLogin($admin_user);
    // Create server.
    $this->drupalGet('admin/config/search/search-api/add-server');

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $page->fillField('Server name', 'Europa search server');
    $assert_session->waitForElementVisible('css', '#edit-id');

    $page->fillField('API key', 'api-key');
    $page->fillField('Database', 'db');
    $page->checkField('Enable ingestion');
    $page->fillField('Info API endpoint', 'http://example.com/search/info');
    $page->fillField('Search API endpoint', 'http://example.com/search/search');
    $page->fillField('Facets API endpoint', 'http://example.com/search/facet');
    $page->fillField('Token API endpoint', 'http://example.com/token');
    $page->fillField('Text ingestion API endpoint', 'http://example.com/ingest/text');
    $page->fillField('File ingestion API endpoint', 'http://example.com/ingest/file');
    $page->fillField('Delete API endpoint', 'http://example.com/ingest/delete');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The server was successfully saved.');

    $server_id = 'europa_search_server';
    $settings = [];
    $settings['settings']['oe_search']['server'][$server_id]['consumer_key'] = (object) [
      'value' => 'consumer key value',
      'required' => TRUE,
    ];
    $settings['settings']['oe_search']['server'][$server_id]['consumer_secret'] = (object) [
      'value' => 'consumer secret value',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Create index.
    $this->drupalGet('admin/config/search/search-api/add-index');
    $page->fillField('Index name', 'Europa search index');
    $assert_session->waitForElementVisible('css', '#edit-id');
    $page->checkField('datasources[entity:user]');
    $assert_session->fieldExists('server')->selectOption('europa_search_server');
    $assert_session->fieldExists('third_party_settings[oe_search][europa_search_entity_mode]')->selectOption('remote');
    $page->pressButton('Save');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('The index was successfully saved.');

    // Was Correctly saved?
    $this->drupalGet("admin/config/search/search-api/index/europa_search_index/edit");
    $this->assertSession()->fieldValueEquals('third_party_settings[oe_search][europa_search_entity_mode]', 'remote');

    // Change to local.
    $assert_session->fieldExists('third_party_settings[oe_search][europa_search_entity_mode]')->selectOption('remote');
    $page->pressButton('Save');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('The index was successfully saved.');

    // Was Correctly saved?
    $this->drupalGet("admin/config/search/search-api/index/europa_search_index/edit");
    $this->assertSession()->fieldValueEquals('third_party_settings[oe_search][europa_search_entity_mode]', 'remote');
  }

}
