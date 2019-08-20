<?php

declare(strict_types = 1);

namespace OpenEuropa\oe_search_enterprise_search\Plugin\search_api\backend;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Annotation\SearchApiBackend;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\QueryInterface;

/**
 * European Commission Enterprise Search backend for search_api.
 *
 * @SearchApiBackend(
 *   id = "oe_search_enterprise_search",
 *   label = @Translation("EC Enterprise Search"),
 *   description = @Translation("Index items using EC Enterprise Search backend.")
 * )
 */
class EnterpriseSearchBackend extends BackendPluginBase implements PluginFormInterface {

  use PluginFormTrait {
    submitConfigurationForm as traitSubmitConfigurationForm;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildConfigurationForm() method.
  }

  public function indexItems(IndexInterface $index, array $items) {
    // TODO: Implement indexItems() method.
  }

  public function deleteItems(IndexInterface $index, array $item_ids) {
    // TODO: Implement deleteItems() method.
  }

  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL) {
    // TODO: Implement deleteAllIndexItems() method.
  }

  public function search(QueryInterface $query) {
    // TODO: Implement search() method.
  }

}
