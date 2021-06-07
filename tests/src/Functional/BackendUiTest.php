<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the backend admin UI.
 *
 * @group oe_search
 */
class BackendUiTest extends BrowserTestBase {

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

  }

}
