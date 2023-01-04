<?php

namespace Drupal\db_wiki\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines WikiController class.
 */
class WikiController extends ControllerBase {

  /**
   * Display the main database page.
   *
   */
  public function database() {
    $legacyContent = $this->getLegacyContent('/database-filter');
    $extractedContent = $this->extractContent(
      $legacyContent,
      '//div[@class="db-articles-section expanded"] | //div[@class="db-articles-section"]',
      FALSE
    );

    return [
      '#markup' => $extractedContent,
      '#title' => '',
    ];
  }

  /**
   * Display a specific database page. This will also extract meta data from the
   * legacy source and apply the information to the current site for this page.
   *
   * @param string $slug
   *
   * @return array
   */
  public function database_article($slug) {
    $legacyContent = $this->getLegacyContent('/database_article/' . $slug);
    $extractedContent = $this->extractContent($legacyContent, '//div[@class="wysiwyg"]');
    $extractedTitle = $this->extractContent($legacyContent, '//h2[@class="page-title"]', TRUE, FALSE);
    $extractedImage = $this->extractContent($legacyContent, '//img[@class="slideshow__carousel-item-image"]');

    // Meta data extraction
    $expectedMetaTags = [
      [
        'name' => 'description',
        'attributes' => [
          'content'
        ]
      ],
      [
        'name' => 'keywords',
        'attributes' => [
          'content'
        ]
      ],
      [
        'property' => 'fb:app_id',
        'attributes' => [
          'content',
          'data-graph-version',
        ],
      ],
      [
        'property' => 'fb:admins',
        'attributes' => [
          'content',
        ],
      ],
      [
        'property' => 'og:description',
        'attributes' => [
          'content',
        ],
      ],
      [
        'property' => 'og:image',
        'attributes' => [
          'content',
        ],
      ],
      [
        'property' => 'og:site_name',
        'attributes' => [
          'content',
        ],
      ],
      [
        'property' => 'og:type',
        'attributes' => [
          'content',
        ],
      ],
      [
        'property' => 'og:title',
        'attributes' => [
          'content',
        ],
      ],
      [
        'property' => 'og:url',
        'attributes' => [
          'content',
        ],
      ],
    ];
    $attached = [];
    foreach ($expectedMetaTags as $metaTag) {
      $metaTagIdentifier = 'name';
      if (key_exists('property', $metaTag)) {
        $metaTagIdentifier = 'property';
      }

      $returnedAttributes = $this->extractMeta($legacyContent, '//meta[@' . $metaTagIdentifier . '="' . $metaTag[$metaTagIdentifier] . '"]', $metaTag['attributes']);

      if (!empty($returnedAttributes)) {
        $attachedTag = [
          '#tag' => 'meta',
          '#attributes' => [
            $metaTagIdentifier => $metaTag[$metaTagIdentifier],
          ],
        ];
        foreach ($returnedAttributes as $key => $value) {
          $attachedTag['#attributes'][$key] = $value;
        }
        $attached['html_head'][] = [$attachedTag, $metaTag[$metaTagIdentifier]];
      }
    }

    return [
      '#markup' => '<div class="row">' . $extractedContent . $extractedImage . "</div>",
      '#title' => $extractedTitle,
      '#attached' => $attached,
    ];
  }

  /**
   * Routing title callback. This is used to bypass the Metatag Module title modification.
   * @param $slug
   *
   * @return string
   */
  public function database_article_title($slug) {
    $legacyContent = $this->getLegacyContent('/database_article/' . $slug);

    return $this->extractContent($legacyContent, '//h2[@class="page-title"]', TRUE, FALSE);
  }

  /**
   * This is an Ajax request Method.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function database_filter(Request $request) {

    $debug = '';
    $filter = '';
    $response = [];

    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
      $data = json_decode($request->getContent(), TRUE);
      if ($data['path'] && is_array($data['path'])) {
        $filter = "/" . implode('/', $data['path']);
      }
      if($data['page']) {
        $filter .= "/page/" . $data['page'];
      }
      if($data['debug'] === TRUE){
        $debug = "<div>$filter</div>";
      }
    }
    $response['html'] = $debug . $this->getLegacyContent('/database-filter' . $filter);

    return new JsonResponse($response);
  }

  private function getLegacyContent($url) {
    /** @var \Drupal\Core\Config\ConfigFactory $config */
    $config = \Drupal::service('config.factory')
      ->getEditable('db_wiki.api_settings');
    $client = new Client([
      'base_uri' => $config->get('db_wiki.endpoint'),
      'timeout' => 0,
      'allow_redirects' => FALSE,
    ]);
    try {
      $request = $client->request('GET', $url);

      return (string) $request->getBody();
    } catch (GuzzleException $exception) {
      \Drupal::logger('db_wiki')->notice($exception->getMessage());

      return [];
    }
  }

  /**
   * @param string $content
   * @param string $identifier
   * @param bool $single
   * @param bool $html
   *
   * @return string
   */
  private function extractContent($content, $identifier, $single = TRUE, $html = TRUE) {
    $doc = new \DOMDocument();
    $doc->loadHTML($content);
    $xpath = new \DOMXPath($doc);
    $elements = $xpath->query($identifier);
    $content = '';

    // If we haven't found any elements, skip this section. This is mainly used for
    // article images. Not all articles have images.
    if ($elements->length != 0) {
      if ($single) {
        if ($html) {
          $content = $doc->saveHTML($elements[0]);
        }
        else {
          $content = $elements[0]->nodeValue;
        }

      }
      else {
        /** @var \DOMNode $element */
        foreach ($elements as $element) {
          $content .= $doc->saveHTML($element);
        }
      }
    }

    return $content;
  }

  /**
   * Handle meta data extraction a bit differently. As we are only searching
   * for specific values from the legacy data, we can accept and return an
   * array of those values.
   *
   * @param string $content
   * @param string $identifier
   * @param array $meta
   *
   * @return string|array
   */
  private function extractMeta($content, $identifier, $meta) {
    $doc = new \DOMDocument();
    $doc->loadHTML($content);
    $xpath = new \DOMXPath($doc);
    $elements = $xpath->query($identifier);
    $content = [];

    if ($elements->length != 0) {
      foreach ($meta as $item) {
        foreach ($elements[0]->attributes as $attribute) {
          if ($attribute->name == $item) {
            $content[$item] = $attribute->value;
          }
        }
      }
    }

    return $content;
  }
}