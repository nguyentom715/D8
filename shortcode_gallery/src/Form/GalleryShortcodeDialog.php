<?php

namespace Drupal\shortcode_gallery\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\SetDialogTitleCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\shortcode_gallery\Exception\EntityBrowserEmptyException;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\EditorInterface;
use Drupal\entity_browser\Events\Events;
use Drupal\entity_browser\Events\RegisterJSCallbacks;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class GalleryShortcodeDialog.
 *
 * @package Drupal\shortcode_gallery\Form
 */
class GalleryShortcodeDialog extends FormBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity browser.
   *
   * @var \Drupal\entity_browser\EntityBrowserInterface
   */
  protected $entityBrowser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a GalleryShortcodeDialog object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Form Builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    FormBuilderInterface $form_builder,
    EntityTypeManagerInterface $entity_type_manager,
    EventDispatcherInterface $event_dispatcher,
    ModuleHandlerInterface $module_handler
  ) {
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gallery_embed_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   The editor to which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, EditorInterface $editor = NULL) {
    $values = $form_state->getValues();
    $input = $form_state->getUserInput();
    // Set embed button element in form state, so that it can be used later in
    // validateForm() function.
    $form_state->set('editor', $editor);
    // Initialize entity element with form attributes, if present.
    $entity_element = empty($values['attributes']) ? [] : $values['attributes'];
    $entity_element += empty($input['attributes']) ? [] : $input['attributes'];
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    if (!$form_state->get('entity_element')) {
      $form_state->set('entity_element', isset($input['editor_object']) ? $input['editor_object'] : []);
    }
    $entity_element += $form_state->get('entity_element');

    $entity_element += [
      'data-entity-type' => 'node',
      'data-entity-uuid' => '',
    ];
    $form_state->set('entity_element', $entity_element);

    $entity = $this->entityTypeManager->getStorage($entity_element['data-entity-type'])
                                      ->loadByProperties(['uuid' => $entity_element['data-entity-uuid']]);
    $form_state->set('entity', current($entity) ?: NULL);

    if (!$form_state->get('step')) {
      // If an entity has been selected, then always skip to the embed options.
      if ($form_state->get('entity')) {
        $form_state->set('step', 'embed');
      }
      else {
        $form_state->set('step', 'select');
      }
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#attached']['library'][] = 'shortcode_gallery/shortcode_gallery.embed.dialog';
    $form['#prefix'] = '<div id="gallery-embed-dialog-form">';
    $form['#suffix'] = '</div>';
    $form['#attributes']['class'][] = 'entity-embed-dialog-step--' . $form_state->get('step');

    $this->loadEntityBrowser($form_state);

    if ($form_state->get('step') == 'select') {
      $form = $this->buildSelectStep($form, $form_state);
    }
    elseif ($form_state->get('step') == 'embed') {
      $form = $this->buildEmbedStep($form, $form_state);
    }

    return $form;
  }

  /**
   * Form constructor for the entity selection step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildSelectStep(array &$form, FormStateInterface $form_state) {
    // Entity element is calculated on every AJAX request/submit.
    // See ::buildForm().
    $entity_element = $form_state->get('entity_element');

    $form['attributes']['data-entity-type'] = [
      '#type' => 'value',
      '#value' => $entity_element['data-entity-type'],
    ];

    $entity_type = $this->entityTypeManager->getDefinition($entity_element['data-entity-type']);

    $form['#title'] = $this->t('Select @type to embed', ['@type' => $entity_type->getLowercaseLabel()]);

    $this->eventDispatcher->addListener(Events::REGISTER_JS_CALLBACKS, [
      $this,
      'registerJSCallback',
    ]);
    $form['entity_browser'] = [
      '#type' => 'entity_browser',
      '#entity_browser' => $this->entityBrowser->id(),
      '#cardinality' => 1,
      '#entity_browser_validators' => [
        'entity_type' => ['type' => $entity_element['data-entity-type']],
      ],
    ];

    $form['attributes']['data-entity-uuid'] = [
      '#type' => 'value',
      '#title' => $entity_element['data-entity-uuid'],
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#button_type' => 'primary',
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitSelectStep',
        'event' => 'click',
      ],
      '#attributes' => [
        'class' => [
          'js-button-next',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Form constructor for the entity embedding step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildEmbedStep(array $form, FormStateInterface $form_state) {
    // Entity element is calculated on every AJAX request/submit.
    // See ::buildForm().
    $entity_element = $form_state->get('entity_element');
    /** @var \Drupal\editor\EditorInterface $editor */
    $editor = $form_state->get('editor');
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->get('entity');
    $values = $form_state->getValues();

    $form['#title'] = $this->t('Embed @type', [
      '@type' => $entity->getEntityType()->getLowercaseLabel(),
    ]);

    $entity_label = '';
    try {
      $entity_label = $entity->link();
    }
    catch (\Exception $e) {
      // Construct markup of the link to the entity manually if link() fails.
      // @see https://www.drupal.org/node/2402533
      $entity_label = '<a href="' . $entity->url() . '">' . $entity->label() . '</a>';
    }

    $form['entity'] = [
      '#type' => 'item',
      '#title' => $this->t('Selected entity'),
      '#markup' => $entity_label,
    ];

    $form['attributes']['data-title'] = [
      '#title' => $this->t('Customize title'),
      '#type' => 'textfield',
      '#default_value' => isset($entity_element['data-title']) ? $entity_element['data-title'] : '',
    ];

    $form['attributes']['data-entity-type'] = [
      '#type' => 'hidden',
      '#value' => $entity_element['data-entity-type'],
    ];
    $form['attributes']['data-entity-uuid'] = [
      '#type' => 'hidden',
      '#value' => $entity_element['data-entity-uuid'],
    ];

    // TODO: Ensure view mode is attached to a bundle?
    $viewModes = \Drupal::service('entity_display.repository')->getViewModes($entity_element['data-entity-type']);
    $allowedViewModes = \Drupal::config('shortcode_gallery.settings')->get('view_modes');

    $viewModeList = [];
    $allowedViewModeList = [];

    // Loop through allowed mode array to see if any are allowed.
    foreach ($allowedViewModes as $viewModeId => $viewMode) {
      if ($viewMode !== '0' && $viewMode !== 0) {
        $allowedViewModeList[] = $viewMode;
      }
    }

    foreach ($viewModes as $viewModeId => $viewMode) {
      // If there no allowed view modes, show all in drop down.
      // If there are allowed view modes, only show those in the drop down.
      if (count($allowedViewModeList) === 0 || in_array($viewModeId, $allowedViewModeList)) {
        $viewModeList[$viewModeId] = $viewMode['label'];
      }
    }

    // If no view modes are allowed, allow default.
    if (!$viewModeList) {
      $viewModeList = [
        'full' => 'Default',
      ];
    }

    $form['attributes']['data-view-mode'] = [
      '#title' => $this->t('View Mode'),
      '#type' => 'select',
      '#default_value' => isset($entity_element['data-view-mode']) ? $entity_element['data-view-mode'] : '',
      '#options' => $viewModeList,
    ];

    $form['attributes']['data-shortcode-id'] = [
      '#type' => 'value',
      '#value' => isset($entity_element['data-shortcode-id']) ? $entity_element['data-shortcode-id'] : 'gallery',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitAndShowSelect',
        'event' => 'click',
      ],
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Embed'),
      '#button_type' => 'primary',
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitEmbedStep',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->get('step') == 'select') {
      $this->validateSelectStep($form, $form_state);
    }
    elseif ($form_state->get('step') == 'embed') {
      $this->validateEmbedStep($form, $form_state);
    }
  }

  /**
   * Form validation handler for the entity selection step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateSelectStep(array $form, FormStateInterface $form_state) {
    if ($form_state->hasValue(['entity_browser', 'entities'])) {
      if (!$form_state->isValueEmpty(['entity_browser', 'entities'])) {
        $id = $form_state->getValue(['entity_browser', 'entities', 0])->id();
      }
      $element = $form['entity_browser'];
    }
    else {
      $id = trim($form_state->getValue(['entity_id']));
      $element = $form['entity_id'];
    }

    if (empty($id)) {
      $form_state->setError($element, $this->t('No gallery was selected.'));
      return;
    }

    $entity_type = $form_state->getValue([
      'attributes',
      'data-entity-type',
    ]);

    if ($entity = $this->entityTypeManager->getStorage($entity_type)
                                          ->load($id)
    ) {
      if (!$entity->access('view')) {
        $form_state->setError($element, $this->t('Unable to access @type entity @id.', [
          '@type' => $entity_type,
          '@id' => $id,
        ]));
      }
      else {
        if ($uuid = $entity->uuid()) {
          $form_state->setValueForElement($form['attributes']['data-entity-uuid'], $uuid);
        }
        else {
          $form_state->setError($element, $this->t('Cannot embed @type entity @id because it does not have a UUID.', [
            '@type' => $entity_type,
            '@id' => $id,
          ]));
        }
      }
    }
    else {
      $form_state->setError($element, $this->t('Unable to load @type entity @id.', [
        '@type' => $entity_type,
        '@id' => $id,
      ]));
    }
  }

  /**
   * Form validation handler for the entity embedding step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateEmbedStep(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Form submission handler to to another step of the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $step
   *   The current form step.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitStep(array &$form, FormStateInterface $form_state, $step) {
    $response = new AjaxResponse();

    $form_state->set('step', $step);
    $form_state->setRebuild(TRUE);
    $rebuild_form = $this->formBuilder->rebuildForm('gallery_embed_dialog', $form_state, $form);
    unset($rebuild_form['#prefix'], $rebuild_form['#suffix']);
    $response->addCommand(new HtmlCommand('#gallery-embed-dialog-form', $rebuild_form));
    $response->addCommand(new SetDialogTitleCommand('', $rebuild_form['#title']));

    return $response;
  }

  /**
   * Form submission handler for the entity selection step.
   *
   * On success will send the user to the next step of the form to select the
   * embed display settings. On form errors, this will rebuild the form and
   * display the error messages.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitSelectStep(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Display errors in form, if any.
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#gallery-embed-dialog-form', $form));
    }
    else {
      $form_state->set('step', 'embed');
      $form_state->setRebuild(TRUE);
      $rebuild_form = $this->formBuilder->rebuildForm('gallery_embed_dialog', $form_state, $form);
      unset($rebuild_form['#prefix'], $rebuild_form['#suffix']);
      $response->addCommand(new HtmlCommand('#gallery-embed-dialog-form', $rebuild_form));
      $response->addCommand(new SetDialogTitleCommand('', $rebuild_form['#title']));
    }

    return $response;
  }

  /**
   * Submit and show select step after submit.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitAndShowSelect(array &$form, FormStateInterface $form_state) {
    return $this->submitStep($form, $form_state, 'select');
  }

  /**
   * Submit and show embed step after submit.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitAndShowEmbed(array $form, FormStateInterface $form_state) {
    return $this->submitStep($form, $form_state, 'embed');
  }

  /**
   * Form submission handler for the entity embedding step.
   *
   * On success this will submit the command to save the embedded entity with
   * the configured display settings to the WYSIWYG element, and then close the
   * modal dialog. On form errors, this will rebuild the form and display the
   * error messages.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitEmbedStep(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Submit configuration form the selected Entity Embed Display plugin.
    $entity_element = $form_state->getValue('attributes');
    $entity = $this->entityTypeManager->getStorage($entity_element['data-entity-type'])
                                      ->loadByProperties(['uuid' => $entity_element['data-entity-uuid']]);
    $entity = current($entity);

    $values = $form_state->getValues();
    // Display errors in form, if any.
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#gallery-embed-dialog-form', $form));
    }
    else {
      // Filter out empty attributes.
      $values['attributes'] = array_filter($values['attributes'], function ($value) {
        return (bool) Unicode::strlen((string) $value);
      });

      // Allow other modules to alter values before submitting to the WYSIWYG.
      $this->moduleHandler->alter('entity_embed_values', $values, $entity, $display, $form_state);

      $response->addCommand(new EditorDialogSave($values));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * Form element validation handler; Escapes the value an element.
   *
   * This should be used for any element in the embed form which may contain
   * HTML that should be serialized as an attribute element on the embed.
   */
  public static function escapeValue($element, FormStateInterface $form_state) {
    if ($value = trim($element['#value'])) {
      $form_state->setValueForElement($element, Html::escape($value));
    }
  }

  /**
   * Registers JS callback.
   *
   * Gets entities from entity browser and updates form values accordingly.
   */
  public function registerJSCallback(RegisterJSCallbacks $event) {
    if ($event->getBrowserID() == $this->entityBrowser->id()) {
      $event->registerCallback('Drupal.ShortcodeGalleryEmbedDialog.selectionCompleted');
    }
  }

  /**
   * Load the current entity browser and its settings from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @throws \Drupal\shortcode_gallery\Exception\EntityBrowserEmptyException
   */
  protected function loadEntityBrowser(FormStateInterface $form_state) {
    $this->entityBrowser = NULL;

    $entityBrowserName = \Drupal::config('shortcode_gallery.settings')->get('entity_browser');

    if ($entityBrowserName) {
      $this->entityBrowser = $this->entityTypeManager->getStorage('entity_browser')
                                                     ->load($entityBrowserName);
    } else {
      throw new EntityBrowserEmptyException(
        'Please select an entity browser in  Shortcode Gallery settings.'
      );
    }

  }

}
