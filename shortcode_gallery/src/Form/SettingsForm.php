<?php

namespace Drupal\shortcode_gallery\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SettingsForm.
 *
 * @package Drupal\shortcode_gallery\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shortcode_gallery_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shortcode_gallery.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('shortcode_gallery.settings');
    $browsers = \Drupal::entityTypeManager()->getStorage('entity_browser')->loadMultiple();
    $browserOptions = [];

    /** @var \Drupal\Core\Entity\EntityInterface $browser */
    foreach ($browsers as $browser) {
      $browserOptions[$browser->id()] = $browser->label();
    }

    $form['entity_browser'] = [
      '#type' => 'radios',
      '#title' => $this->t('Entity Browser'),
      '#description' => 'Choose the entity browser to use for adding videos to a WYSIWYG.',
      '#options' => $browserOptions,
      '#default_value' => $config->get('entity_browser'),
    ];

    $viewModeOptions = [
      'full' => 'Default',
    ];
    $viewModes = \Drupal::service('entity_display.repository')->getViewModes('node');

    foreach ($viewModes as $viewModeId => $viewMode) {
      $viewModeOptions[$viewModeId] = $viewMode['label'];
    }

    $form['view_modes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('View Modes'),
      '#description' => 'Choose the view modes eligible for display in the Video Shortcode Dialog Form. Check none to allow all view modes.',
      '#options' => $viewModeOptions,
      '#default_value' => $config->get('view_modes') ?: [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config('shortcode_gallery.settings')
      // Set the submitted configuration setting.
      ->set('entity_browser', $form_state->getValue('entity_browser'))
      ->set('view_modes', $form_state->getValue('view_modes'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
