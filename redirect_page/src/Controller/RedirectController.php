<?php

namespace Drupal\redirect_page\Controller; 
use Drupal\Core\Controller\ControllerBase;

/**
* Get redirect for external link.
*/
class RedirectController extends ControllerBase {
  const REDIRECTION_PAGE_DEFAULT_MESSAGE = 'We are redirecting you to <a id="external_link">here</a>.';
  public function redirect_page_render() {
    $msg =  \Drupal::state()->get('redirect_page_message', self::REDIRECTION_PAGE_DEFAULT_MESSAGE);
    $items = array(
        '#type' => 'markup',
        '#markup' => $msg,
    );

  $safe_regexp = \Drupal::state()->get('redirect_page_safe_regexp', FALSE);
  $new_window = \Drupal::state()->get('redirect_new_window', TRUE);

  if ($safe_regexp) {
    $items['#attached']['library'][] = 'redirect_page/js-set';
    $items['#attached']['drupalSettings']['redirect_page']['redirect_page_safe_href_regexp'] = $safe_regexp;
    $items['#attached']['drupalSettings']['redirect_page']['redirect_new_window'] = $new_window;
  }
  return $items;
  }
}