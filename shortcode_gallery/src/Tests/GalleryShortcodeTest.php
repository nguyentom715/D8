<?php

namespace Drupal\shortcode_gallery\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Wa72\HtmlPageDom\HtmlPageCrawler;

/**
 * Tests the shortcode_gallery replacement.
 *
 * @group shortcode_gallery
 */
class GalleryShortcodeTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'shortcode',
    'shortcode_gallery',
  ];

  /**
   * The shortcode renderer.
   *
   * @var \Drupal\shortcode\ShortcodeRenderer
   */
  private $shortcodeRenderer;

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $this->shortcodeRenderer = \Drupal::service('shortcode.renderer');
  }

  /**
   * Display the shortcode if it is set to render.
   */
  public function testProcessWithRender() {
    $formatSettings['thirdPartySettings']['shortcode_gallery']['shortcode_gallery_display'] = "1";

    $this->createContentType([
      'type' => 'page',
    ]);
    $node = $this->drupalCreateNode();

    $sets = [
      [
        'shortcodeHtml' => (new HtmlPageCrawler('<div></div>'))
          ->addClass('shortcode')
          ->setAttribute('data-shortcode-entity-uuid', $node->uuid())
          ->setAttribute('data-shortcode-entity-type', 'node')
          ->setAttribute('data-shortcode-id', 'gallery')
          ->setAttribute('data-shortcode-view-mode', 'default')
          ->setAttribute('id', 'gallery-shortcode-0')
          ->saveHTML(),
        'expected' => [
          'title' => $node->label(),
          'href' => Url::fromRoute(
            'entity.node.canonical',
            ['node' => $node->id()],
            ['absolute' => FALSE])
            ->toString(),
        ],
      ],
    ];

    foreach ($sets as $set) {
      $actualOutput = $this->shortcodeRenderer->process($set['shortcodeHtml'], $formatSettings)['html'];
      // Is the title correct?
      $this->assertEqual(
        (new HtmlPageCrawler($actualOutput))
          ->filter('article')
          ->filter('h2')
          ->filter('a')
          ->filter('span.field--name-title')
          ->getInnerHtml(),
        $set['expected']['title']
      );

      // Does the link open correctly?
      $this->assertEqual(
        (new HtmlPageCrawler($actualOutput))
          ->filter('article')
          ->filter('h2')
          ->filter('a')
          ->getAttribute('href'),
        $set['expected']['href']
      );
    }
  }

  /**
   * Do not display the shortcode if it is not set to render.
   */
  public function testProcessWithoutRender() {
    $formatSettings['thirdPartySettings']['shortcode_gallery']['shortcode_gallery_display'] = "0";

    $this->createContentType([
      'type' => 'page',
    ]);
    $node = $this->drupalCreateNode();

    $set = [
      'shortcodeHtml' => (new HtmlPageCrawler('<div></div>'))
        ->addClass('shortcode')
        ->setAttribute('data-shortcode-entity-uuid', $node->uuid())
        ->setAttribute('data-shortcode-entity-type', 'node')
        ->setAttribute('data-shortcode-id', 'gallery')
        ->setAttribute('data-shortcode-view-mode', 'default')
        ->setAttribute('id', 'gallery-shortcode-0')
        ->saveHTML(),
    ];

    $this->assertEqual(
      '',
      $this->shortcodeRenderer->process($set['shortcodeHtml'], $formatSettings)['html']
    );
  }

  /**
   * Do not display the shortcode if there is no referenced entity id.
   */
  public function testProcessMissingEntityUuid() {
    $formatSettings['thirdPartySettings']['shortcode_gallery']['shortcode_gallery_display'] = "1";

    $set = [
      'shortcodeHtml' => (new HtmlPageCrawler('<div></div>'))
        ->addClass('shortcode')
        ->setAttribute('data-shortcode-entity-type', 'node')
        ->setAttribute('data-shortcode-id', 'gallery')
        ->setAttribute('data-shortcode-view-mode', 'default')
        ->setAttribute('id', 'gallery-shortcode-0')
        ->saveHTML(),
    ];

    $this->assertEqual(
      '',
      $this->shortcodeRenderer->process($set['shortcodeHtml'], $formatSettings)['html']
    );
  }

  /**
   * Do not display the shortcode if there is no referenced entity id.
   */
  public function testProcessMissingEntityType() {
    $formatSettings['thirdPartySettings']['shortcode_gallery']['shortcode_gallery_display'] = "1";

    $node = $this->drupalCreateNode();

    $set = [
      'shortcodeHtml' => (new HtmlPageCrawler('<div></div>'))
        ->addClass('shortcode')
        ->setAttribute('data-shortcode-entity-uuid', $node->uuid())
        ->setAttribute('data-shortcode-id', 'gallery')
        ->setAttribute('data-shortcode-view-mode', 'default')
        ->setAttribute('id', 'gallery-shortcode-0')
        ->saveHTML(),
    ];

    $this->assertEqual(
      '',
      $this->shortcodeRenderer->process($set['shortcodeHtml'], $formatSettings)['html']
    );
  }

  /**
   * Do not display the shortcode if there is no view mode attribute.
   */
  public function testProcessMissingViewMode() {
    $formatSettings['thirdPartySettings']['shortcode_gallery']['shortcode_gallery_display'] = "1";

    $node = $this->drupalCreateNode();

    $set = [
      'shortcodeHtml' => (new HtmlPageCrawler('<div></div>'))
        ->addClass('shortcode')
        ->setAttribute('data-shortcode-entity-uuid', $node->uuid())
        ->setAttribute('data-shortcode-entity-type', 'node')
        ->setAttribute('data-shortcode-entity', $node->id())
        ->setAttribute('data-shortcode-id', 'gallery')
        ->setAttribute('id', 'gallery-shortcode-0')
        ->saveHTML(),
    ];

    $this->assertEqual(
      '',
      $this->shortcodeRenderer->process($set['shortcodeHtml'], $formatSettings)['html']
    );
  }

}
