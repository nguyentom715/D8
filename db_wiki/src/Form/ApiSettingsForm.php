<?php

namespace Drupal\db_wiki\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApiSettingsForm.
 */
class ApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'db_wiki.api_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('db_wiki.api_settings');
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api Endpoint'),
      '#description' => $this->t('Where should the api point to for article information.'),
      '#default_value' => $config->get('db_wiki.endpoint'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('db_wiki.api_settings')
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->save();
  }

}
