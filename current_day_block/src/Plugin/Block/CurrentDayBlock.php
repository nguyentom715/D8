<?php

namespace Drupal\current_day_block\Plugin\Block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockBase;

/**
 *
 *
 * @Block(
 *   id = "list_nodes",
 *   admin_label = @Translation("Current Day Block"),
 *   category = @Translation("Custom"),
 * )
 */
class CurrentDayBlock extends BlockBase implements BlockPluginInterface
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    global $base_url;
    $config = $this->getConfiguration();
    $content_types = $config['enabled_content_types'];
    $result = [];
    $list = '';
    foreach ($content_types as $label) {
      if (is_string($label)) {
        $nids = \Drupal::entityQuery('node')->condition('type', $label)->execute();
        if (count($nids) != 0) {
          $result = array_merge($result, $nids);
        }
      }
    }
    $nodes =  \Drupal\node\Entity\Node::loadMultiple($result);

    $compare_function = function ($a, $b) {
      return $b->changed->value <=> $a->changed->value;
    };
    usort($nodes, $compare_function);
    foreach ($nodes as $node) {
      $node_day = date('d', $node->changed->value);
      $current_day = date('d');
      $nid = $node->nid->value;
      if ($node_day == $current_day) {
        $list .= "<a href='$base_url/node/$nid'>" . $node->title->value . "</a><br/>";
      }
    }
    return [
      '#markup' => $list,
      '#cache' => ['max-age' => 0,],
    ];
  }

   /**
   * Implement block form
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    foreach ($types as $type) {
      $content_types[$type->id()] = $type->label();
    }
    $defaults = array_keys($types);
    // Need help setting this up so that the options are content types. 
    $form['enabled_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => 'Enabled Content Types',
      '#description' => 'Run preprocess for all enabled content types only',
      '#options' => $content_types,
      '#default_value' => isset($config['enabled_content_types']) ? $config['enabled_content_types'] : $defaults,
    ];

    return $form;
  }
  
  /**
   * Implement block submit
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['enabled_content_types'] = $values['enabled_content_types'];
  }
}
