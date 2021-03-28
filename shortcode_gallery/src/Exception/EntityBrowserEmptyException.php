<?php

namespace Drupal\shortcode_gallery\Exception;

/**
 * Class EntityBrowserEmptyException.
 *
 * This exception is thrown when the user tries to open
 * a Shortcode Gallery dialog box without first
 * setting the associated entity browser.
 *
 * @package Drupal\shortcode_gallery\Exception
 */
class EntityBrowserEmptyException extends \Exception {
}
