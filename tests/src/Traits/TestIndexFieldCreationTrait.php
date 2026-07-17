<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_search\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Creates the bundles and fields required by the test search index.
 */
trait TestIndexFieldCreationTrait {

  /**
   * Creates the bundles and fields referenced by the test search index.
   *
   * The "europa_search_index" index shipped by oe_search_test indexes several
   * fields of the "entity_test_mulrev_changed" entity type. Recent Drupal core
   * versions resolve the data definition of every indexed field when the
   * index/view configuration is installed, so the bundles and fields have to
   * exist before installing the oe_search_test config.
   */
  protected function createTestIndexFields(): void {
    $this->createTestBundle('item', NULL, 'entity_test_mulrev_changed');
    $this->createTestBundle('article', NULL, 'entity_test_mulrev_changed');

    $field_types = [
      'body' => 'text',
      'keywords' => 'text',
      'highlighted' => 'boolean',
      'publication_date' => 'datetime',
      'cron_time' => 'datetime',
    ];
    foreach ($field_types as $field_name => $field_type) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'entity_test_mulrev_changed',
        'type' => $field_type,
      ])->save();
      foreach (['item', 'article'] as $bundle) {
        FieldConfig::create([
          'field_name' => $field_name,
          'entity_type' => 'entity_test_mulrev_changed',
          'bundle' => $bundle,
        ])->save();
      }
    }
  }

  /**
   * Creates a new bundle for entity_test entities.
   *
   * @param string $bundle
   *   The machine-readable name of the bundle.
   * @param string|null $text
   *   (optional) The human-readable name of the bundle. If none is provided,
   *   the machine name will be used.
   * @param string $entity_type
   *   (optional) The entity type for which the bundle is created. Defaults to
   *   'entity_test'.
   *
   * @todo Remove after drupal:12.0.0. Use
   *   \Drupal\entity_test\EntityTestHelper::createBundle() instead.
   */
  protected function createTestBundle(string $bundle, ?string $text = NULL, string $entity_type = 'entity_test'): void {
    $bundles = \Drupal::state()->get($entity_type . '.bundles', [$entity_type => ['label' => 'Entity Test Bundle']]);
    $bundles += [$bundle => ['label' => $text ?: $bundle]];
    \Drupal::state()->set($entity_type . '.bundles', $bundles);
    \Drupal::service('entity_bundle.listener')->onBundleCreate($bundle, $entity_type);
  }

}
