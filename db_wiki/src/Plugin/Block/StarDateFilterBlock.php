sta<?php

namespace Drupal\db_wiki\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Star Date Filter' block.
 *
 * @Block(
 *   id = "db_wiki_star_date_filter_block",
 *   admin_label = @Translation("DB Wiki Star Date Filter Block"),
 *   category = @Translation("Content")
 * )
 */
class StarDateFilterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $filters = [
      'Aliens'            => 'alien',
      'Characters'        => 'character',
      'Food'              => 'food',
      'Places'            => 'places',
      'Science & Medical' => 'science_and_medical',
      'Ships'             => 'ship',
      'Technology'        => 'technology',
    ];

    return [
      '#theme' => 'db_wiki_star_date_filter',
      '#filters' => $filters,
    ];
  }

}
