<?php

namespace Drupal\oe_search\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a OpenEuropa Search block.
 *
 * @Block(
 *   id = "oe_search",
 *   admin_label = @Translation("OpenEuropa Search block"),
 *   category = @Translation("Search"),
 * )
 */
class OeSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\oe_search\Form\OeSearchBlockForm');
  }

}
