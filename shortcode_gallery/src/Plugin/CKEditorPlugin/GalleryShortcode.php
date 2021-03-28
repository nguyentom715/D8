<?php

namespace Drupal\shortcode_gallery\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginButtonsInterface;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "GalleryShortcode" plugin.
 *
 * @CKEditorPlugin(
 *   id = "galleryshortcode",
 *   label = @Translation("Gallery Shortcode"),
 *   module = "shortcode_gallery"
 * )
 */
class GalleryShortcode extends CKEditorPluginBase implements CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return sprintf(
      '%s/js/plugins/%s/plugin.js',
      drupal_get_path('module', 'shortcode_gallery'),
      $this->getPluginId()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Galleryshortcode' => [
        'label' => t('Gallery Shortcode'),
        'image' => sprintf(
          '%s/js/plugins/%s/icons/%s.png',
            drupal_get_path('module', 'shortcode_gallery'),
            $this->getPluginId(),
            $this->getPluginId()
        ),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'shortcode/shortcode.embed',
    ];
  }

}
