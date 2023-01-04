<?php

namespace Drupal\db_wiki\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Movies Filter' block.
 *
 * @Block(
 *   id = "db_wiki_movies_filter_block",
 *   admin_label = @Translation("DB Wiki Movies Filter Block"),
 *   category = @Translation("Content")
 * )
 */
class MoviesFilterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $filters = [
      'Networks: Discovery'                    => 'networks-discovery',
      'Networks Short Treks'                   => 'networks-short-treks',
      'Networks: The Original Series'          => 'networks-the-original-series',
      'Networks: The Next Generation'          => 'networks-the-next-generation',
      'Networks: Deep Space Nine'              => 'networks-deep-space-nine',
      'Networks: Voyager'                      => 'networks-voyager',
      'Networks: Enterprise'                   => 'networks-enterprise',
      'Networks: The Animated Series'          => 'networks-the-animated-series',
      'Networks: The Motion Picture'           => 'networks-the-motion-picture',
      'Networks II: The Wrath of Khan'         => 'networks-ii-the-wrath-of-khan',
      'Networks III: The Search for Spock'     => 'networks-iii-the-search-for-spock',
      'Networks IV: The Voyage Home'           => 'networks-iv-the-voyage-home',
      'Networks V: The Final Frontier'         => 'networks-v-the-final-frontier',
      'Networks VI: The Undiscovered Country'  => 'networks-vi-the-undiscovered-country',
      'Networks Generations'                   => 'networks-generations',
      'Networks First Contact'                 => 'networks-first-contact',
      'Networks Insurrection'                  => 'networks-insurrection',
      'Networks Nemesis'                       => 'networks-nemesis',
      'Networks (2009)'                        => 'networks-2009',
      'Networks Into Darkness'                 => 'networks-into-darkness',
      'Networks Beyond'                        => 'networks-beyond',
    ];

    return [
      '#theme' => 'db_wiki_movies_filter',
      '#filters' => $filters,
    ];
  }

}
