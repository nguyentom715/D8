<?php

namespace Drupal\current_day_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 *
 *
 * @Block(
 *   id = "current_day_block",
 *   admin_label = @Translation("Current Day Block"),
 *   category = @Translation("Custom"),
 * )
 */
class CurrentDayBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    global $base_url;
    $nids = \Drupal::entityQuery('node')->execute();
    $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);
    $list = '';
    foreach ($nodes as $node) {
      $node_day = date('d',$node->changed->value);
      $current_day = date('d');
      $nid = $node->nid->value;
      if($node_day == $current_day){
        $list .= "<a href='$base_url/node/$nid'>".$node->title->value."</a><br/>";
      }
    }  
    return [
      '#markup' => $list,
    ];
  }

}