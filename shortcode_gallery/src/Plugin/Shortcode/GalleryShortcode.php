<?php

namespace Drupal\shortcode_gallery\Plugin\Shortcode;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\shortcode\ShortcodePluginBase;
use Drupal\shortcode\Exception\ShortcodeHtmlAttributeException;

/**
 * The GalleryShortcode class.
 *
 * @Shortcode(
 *   id = "gallery",
 *   title = @Translation("Gallery"),
 *   description = @Translation("Embed a gallery"),
 * )
 */
class GalleryShortcode extends ShortcodePluginBase {

  /**
   * Embed gallery shortcodes.
   *
   * {@inheritdoc}
   */
  public function process() {
    if ($this->getConfiguration()['settings']['format_settings']['display']) {
      // Get the entity.
      /** @var \Drupal\node\NodeInterface $node */
      $node = \Drupal::service('entity.repository')->loadEntityByUuid(
        $this->getConfiguration()['settings']['render_attributes']['data-entity-type'],
        $this->getConfiguration()['settings']['render_attributes']['data-entity-uuid']
      );

      if ($node) {
        /** @var \Drupal\Core\Entity\EntityViewBuilderInterface $builder */
        $builder = \Drupal::entityTypeManager()->getViewBuilder($node->getEntityTypeId());
        $viewMode = $this->getConfiguration()['settings']['render_attributes']['data-view-mode'];
        $formatSettings = $this->getConfiguration()['settings']['format_settings'];

        if (!empty($this->getConfiguration()['settings']['render_attributes']['data-title'])) {
          $node->setTitle($this->getConfiguration()['settings']['render_attributes']['data-title']);
        }

        // If a channel is set, generate an iFrame for the RSS feed.
        if (isset($formatSettings['channel'])) {
          $iframeRenderArray = [
            '#theme' => 'distribution_iframe',
            '#gallery' => $node,
            '#variables' => [
              'channel' => $formatSettings['channel'],
              'mode' => $viewMode,
            ],
          ];

          $iframeRendered = $this->render($iframeRenderArray);

          // There is a check in the template to make sure that the Brightcove entity exists. If it doesn't exist, it
          // will return an empty string. If that occurs, the __toString method would fail.
          if (!empty($iframeRendered)) {
            return $iframeRendered->__toString();
          }
          else {
            return '';
          }
        }

        $renderArray = $builder->view($node, $viewMode);

        // Make sure all cache tags and contexts have been included before rendering.
        $renderArray['#cache']['tags'] = !empty($renderArray['#cache']['tags']) ? Cache::mergeTags($renderArray['#cache']['tags'], $this->getCacheTags()) : $this->getCacheTags();
        $renderArray['#cache']['contexts'] = !empty($renderArray['#cache']['contexts']) ? Cache::mergeContexts($renderArray['#cache']['context'], $this->getCacheContexts()) : $this->getCacheContexts();

        $galleryHtml = $this->render($renderArray)->__toString();

        return $galleryHtml;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeKeys() {
    return [
      'data-view-mode',
      'data-entity-type',
      'data-entity-uuid',
      'data-title',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatSettings(array $config) {
    $settings['display'] = FALSE;

    if (
      isset($config['thirdPartySettings']['shortcode_gallery']['shortcode_gallery_display']) &&
      $config['thirdPartySettings']['shortcode_gallery']['shortcode_gallery_display'] == TRUE
    ) {
      $settings['display'] = TRUE;
    }

    if (isset($config['thirdPartySettings']['distribution']['distribution_channel_display'])) {
      $settings['channel'] = $config['thirdPartySettings']['distribution']['distribution_channel_display'];
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderAttributes(array $config) {
    if ($config['data-view-mode']) {
      $attributes['data-view-mode'] = $config['data-view-mode'];
    }
    else {
      throw new ShortcodeHtmlAttributeException(sprintf(
        'Gallery shortcode requires a `%s` attribute.',
        'data-view-mode'
      ));
    }

    if ($config['data-entity-type']) {
      $attributes['data-entity-type'] = $config['data-entity-type'];
    }
    else {
      throw new ShortcodeHtmlAttributeException(sprintf(
        'Gallery shortcode requires a `%s` attribute.',
        'data-entity-type'
      ));
    }

    if ($config['data-entity-uuid']) {
      $attributes['data-entity-uuid'] = $config['data-entity-uuid'];
    }
    else {
      throw new ShortcodeHtmlAttributeException(sprintf(
        'Gallery shortcode requires a `%s` attribute.',
        'data-entity-uuid'
      ));
    }

    if ($config['data-title']) {
      $attributes['data-title'] = $config['data-title'];
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cacheTags = [];
    /** @var \Drupal\node\NodeInterface $galleryNode */
    $galleryNode = \Drupal::service('entity.repository')->loadEntityByUuid(
      $this->getConfiguration()['settings']['render_attributes']['data-entity-type'],
      $this->getConfiguration()['settings']['render_attributes']['data-entity-uuid']
    );

    if ($galleryNode) {
      $cacheTags = Cache::mergeTags($cacheTags, $galleryNode->getCacheTags());
    }

    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node) {
      $cacheTags = Cache::mergeTags($cacheTags, $node->getCacheTags());
    }

    return $cacheTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cacheContexts = [];

    /** @var \Drupal\node\NodeInterface $galleryNode */
    $galleryNode = \Drupal::service('entity.repository')->loadEntityByUuid(
      $this->getConfiguration()['settings']['render_attributes']['data-entity-type'],
      $this->getConfiguration()['settings']['render_attributes']['data-entity-uuid']
    );

    if ($galleryNode) {
      $cacheContexts = Cache::mergeContexts($cacheContexts, $galleryNode->getCacheContexts());
    }

    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node) {
      $cacheContexts = Cache::mergeContexts($cacheContexts, $node->getCacheContexts());
    }

    return $cacheContexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    $libraries = [];

    return $libraries;
  }

}
