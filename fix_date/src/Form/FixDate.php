<?php

namespace Drupal\fix_date\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base handler for FixDate forms.
 *
 * get date form
 */
class FixDate extends ConfigFormBase
{
    protected function getEditableConfigNames()
    {
        return [
            'fix_date.form',
        ];
    }

    public function getFormId()
    {
        return 'fix_date';
    }
    
    /**
   * The current field date.
   *
   * Submit to update the date
   */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['field_date_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Field date name'),
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
        ];
        return $form;
    }
    
   /**
   * The current date.
   *
   * Update formate date
   */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $get_field_name = trim($form_state->getValue('field_date_name'));
        $field_date_name = $get_field_name.'_value';
        $table = 'node__'.$get_field_name;
        $query = \Drupal::database()->select("$table", 'nfd');
        $query->fields('nfd', ['entity_id', "$field_date_name"]);
        $results = $query->execute();
        \Drupal::database()->query("SET sql_mode = ''");
        while($row = $results->fetchAll() ){
            $query = \Drupal::database()->update($table);
            $query->fields([
                "$field_date_name" => DATE(STR_TO_DATE($field_date_name, '%Y-%m-%dT%H:%i:%S')),
            ]);
            $query->condition( 'entity_id', $row['entity_id']);
            $query->execute();
        }
        drupal_set_message("Fixed $field_date_name success");
    }
}