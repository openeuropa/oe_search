<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_search_ingestion_tracking\Functional;

use Drupal\oe_search_ingestion_tracking\Entity\SearchIngestionTracking;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the ingestion tracking dashboard.
 */
class SearchIngestionDashboardTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'language',
    'options',
    'views',
    'oe_search',
    'oe_search_ingestion_tracking',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the dashboard.
   */
  public function testDashboard(): void {
    $assert_session = $this->assertSession();
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    // Access denied without permission.
    $this->drupalGet('admin/content/oe-search-ingestion-tracking');
    $assert_session->statusCodeEquals(403);
    $this->drupalLogout();

    // User with permission.
    $admin_user = $this->drupalCreateUser(['access search ingestion tracking']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/content/oe-search-ingestion-tracking');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Displaying 0 - 0 of 0');

    // Creates search ingestions.
    $this->createSearchIngestionTracking('node', 1, 'en', 0);
    $this->createSearchIngestionTracking('node', 2, 'fr', 2);
    $this->createSearchIngestionTracking('node', 2, 'en', 3);

    $this->drupalGet('admin/content/oe-search-ingestion-tracking');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Displaying 1 - 3 of 3');

    // Check headers.
    $expected_headers = [
      'Tracking ID',
      'Entity type',
      'Entity ID',
      'Retries',
      'Created',
      'Last check',
      'Status',
    ];
    foreach ($expected_headers as $header) {
      $assert_session->elementExists('css', 'table thead th:contains("' . $header . '")');
    }
    $rows = $this->getSession()->getPage()->findAll('css', 'table tbody tr');
    $this->assertCount(3, $rows);
    foreach ($rows as $row) {
      $tracking_id_cell = $row->find('css', 'td:nth-child(1)');
      $this->assertNotNull($tracking_id_cell);
      $this->assertNotEmpty(trim($tracking_id_cell->getText()));
    }

    // Filter by status.
    $this->drupalGet('admin/content/oe-search-ingestion-tracking');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('Status', 'Finished');
    $page->pressButton('Apply');

    $assert_session->pageTextContains('Displaying 1 - 1 of 1');
  }

  /**
   * Creates a search ingestion tracking.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   * @param string $langcode
   *   The langcode.
   * @param int $status
   *   The status.
   */
  protected function createSearchIngestionTracking(string $entity_type, int $entity_id, string $langcode, int $status): void {
    $search_ingestion_tracking = SearchIngestionTracking::create([
      'tracking_id' => \Drupal::service('uuid')->generate(),
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'langcode' => $langcode,
      'status' => $status,
    ]);
    $search_ingestion_tracking->save();
  }

}
