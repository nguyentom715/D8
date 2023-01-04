<?php

namespace Drupal\db_wiki\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Alpha Filter' block.
 *
 * @Block(
 *   id = "db_wiki_alpha_filter_block",
 *   admin_label = @Translation("DB Wiki Alpha Filter Block"),
 *   category = @Translation("Content")
 * )
 */
class AlphaFilterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $filters = [
      'All' => 'all',
      '#'   => 'non-alpha',
    ];
    $alphas = array_combine(range('A', 'Z'), range('a', 'z'));
    $filters = array_merge($filters, $alphas);

    return [
      '#theme' => 'db_wiki_alpha_filter',
      '#filters' => $filters,
    ];
  }

}
