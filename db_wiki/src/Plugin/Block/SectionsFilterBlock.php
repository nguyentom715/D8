<?php

namespace Drupal\db_wiki\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Sections Filter' block.
 *
 * @Block(
 *   id = "db_wiki_sections_filter_block",
 *   admin_label = @Translation("DB Wiki Sections Filter Block"),
 *   category = @Translation("Content")
 * )
 */
class SectionsFilterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $filters = [
      'Episodes' => 'episode',
      'Series Synopses' => 'series_synopsis',
      'Movie Synopses' => 'movie_synopsis',
    ];

    return [
      '#theme' => 'db_wiki_sections_filter',
      '#filters' => $filters,
    ];
  }

}
