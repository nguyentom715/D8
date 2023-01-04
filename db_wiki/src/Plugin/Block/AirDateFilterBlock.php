<?php

namespace Drupal\db_wiki\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an 'Air Date Filter' block.
 *
 * @Block(
 *   id = "db_wiki_air_date_filter_block",
 *   admin_label = @Translation("DB Wiki Air Date Filter Block"),
 *   category = @Translation("Content")
 * )
 */
class AirDateFilterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $filters = [
      'Aliens'            => 'alien',
      'Cast'              => 'cast',
      'Characters'        => 'character',
      'Creative Staff'    => 'creative_staff',
      'Food'              => 'food',
      'Places'            => 'places',
      'Science & Medical' => 'science_and_medical',
      'Ships'             => 'ship',
      'Technology'        => 'technology',
    ];

    return [
      '#theme' => 'db_wiki_air_date_filter',
      '#filters' => $filters,
    ];
  }

}
