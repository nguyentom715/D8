<?php

namespace Drupal\shortcode_gallery\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\shortcode\Ajax\ShortcodeEmbedInsertCommand;
use Drupal\filter\FilterFormatInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wa72\HtmlPageDom\HtmlPageCrawler;

/**
 * Class CKEditorPreviewController.
 *
 * @package Drupal\shortcode_image\Controller
 */
class CKEditorPreviewController extends ControllerBase {

  /**
   * Returns an Ajax response to generate preview of embedded items.
   *
   * Expects the the HTML element as GET parameter.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\filter\FilterFormatInterface $filter_format
   *   The filter format.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if 'value' parameter is not found in the request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The preview of the embedded item specified by the data attributes.
   */
  public function preview(Request $request, FilterFormatInterface $filter_format) {
    $text = $request->get('value');
    if ($text == '') {
      throw new NotFoundHttpException();
    }

    // Parse shortcode HTML.
    $shortcode = new HtmlPageCrawler($text);

    // Return the gallery node.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = \Drupal::service('entity.repository')->loadEntityByUuid(
      $shortcode->getAttribute('data-entity-type'),
      $shortcode->getAttribute('data-entity-uuid')
    );

    $render = sprintf('Related Gallery: %s', $entity->label());

    $response = new AjaxResponse();
    $response->addCommand(new ShortcodeEmbedInsertCommand($render));
    return $response;
  }

}
