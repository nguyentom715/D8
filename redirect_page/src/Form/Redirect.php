<?php

namespace Drupal\redirect_page\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Get redirect for external link
*/
class Redirect extends ConfigFormBase
{
    const REDIRECTION_PAGE_DEFAULT_MESSAGE = 'We are redirecting you to <a id="external_link">here</a>.';
    const REDIRECTION_PAGE_SAFE_REGEXP = '(\.gov|\.mil)';
    protected function getEditableConfigNames()
    {
        return [
            'redirect_page.config',
        ];
    }
	
	/**
	* Get form id
	*/
    public function getFormId()
    {
        return 'redirect_page_config_form';
    }

	/**
	* Display message
	*/
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('redirect_page.config');
        $form['redirect_page_message'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Redirection message'),
        '#description' => $this->t('Message to be displayed on unsafe link redirection. Like to external page must be wrapped in an anchor with id "external_link".'),
        '#size' => 6,
        '#default_value' => \Drupal::state()->get('redirect_page_message', self::REDIRECTION_PAGE_DEFAULT_MESSAGE),
        ];
        $form['redirect_page_safe_regexp'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Regular expression for safe links'),
        '#description' => $this->t('A regular expression that indicates a non-external link.'),
        '#size' => 150,
        '#default_value' => \Drupal::state()->get('redirect_page_safe_regexp', self::REDIRECTION_PAGE_SAFE_REGEXP),
        ];
        return parent::buildForm($form, $form_state);
    }
    
	/**
	* Redirect display message
	*/    
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitForm($form, $form_state);
        \Drupal::state()->set('redirect_page_message', $form_state->getValue('redirect_page_message'));
        \Drupal::state()->set('redirect_page_safe_regexp', $form_state->getValue('redirect_page_safe_regexp')); 
    }
}