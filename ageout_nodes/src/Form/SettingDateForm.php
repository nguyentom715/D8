<?php

namespace Drupal\tsa_ageout_nodes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Configure settings date for this site.
 */
class SettingDateForm  extends ConfigFormBase
{

    const SETTINGS = 'tsa_ageout_nodes.settings';

    public function getFormId()
    {
        return 'tsa_aegout_nodes_admin';
    }

    protected function getEditableConfigNames()
    {
        return [
            static::SETTINGS,
        ];
    }

	/**
	 * Implements hook_install().
	 */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config(static::SETTINGS);
        $form['blog_group'] = array(
            '#type' => 'fieldset',
            '#title' => t('Remove Blog'),
            '#collapsible' => FALSE,
            '#collapsed' => FALSE,
            '#suffix' => '<p>&nbsp;</p>',
        );
        $form['blog_group']['blog_start_date'] = array(
            '#type' => 'datetime',
            '#title' => t('Start date'),
            '#required' => TRUE,
            '#default_value' => $config->get('blog_start_date') ? DrupalDateTime::createFromTimestamp($config->get('blog_start_date')) : NULL,
        );
        $form['blog_group']['blog_end_date'] = array(
            '#type'             => 'datetime',
            '#title'            => t('End date'),
            '#required' => TRUE,
            '#default_value'    => $config->get('blog_end_date') ?  DrupalDateTime::createFromTimestamp($config->get('blog_end_date')) : NULL,
        );
        $form['press_room_group'] = array(
            '#type' => 'fieldset',
            '#title' => t('Remove News'),
            '#collapsible' => FALSE,
            '#collapsed' => FALSE,
            '#suffix' => '<p>&nbsp;</p>',
        );
        $form['press_room_group']['press_room_start_date'] = array(
            '#type' => 'datetime',
            '#title' => t('Start date'),
            '#required' => TRUE,
            '#default_value' => $config->get('press_room_start_date') ? DrupalDateTime::createFromTimestamp($config->get('press_room_start_date')) : NULL,
        );
        $form['press_room_group']['press_room_end_date'] = array(
            '#type'             => 'datetime',
            '#title'            => t('End date'),
            '#required' => TRUE,
            '#default_value'    => $config->get('press_room_end_date') ? DrupalDateTime::createFromTimestamp($config->get('press_room_end_date')) : NULL,
        );
        $form['autocomplete'] = array(
            '#title' => t('Sreach Title'),
            '#type' => 'entity_autocomplete',
            '#target_type' => 'node',
            '#tags' => TRUE,
            '#selection_settings' => array(
                'target_bundles' => array('blog_post', 'press_room'),
            ),
            '#default_value'    => \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($this->RemoveTartgetId($config->get('autocomplete'))),
        );
        return parent::buildForm($form, $form_state);
    }

	/**
	 * Implements submitform().
	 */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $blog_from_year = $form_state->getValue('blog_start_date')->getTimeStamp();
        $blog_to_year = $form_state->getValue('blog_end_date')->getTimeStamp();
        $this->removeBLogNodes($blog_from_year, $blog_to_year);
        $news_from_year = $form_state->getValue('press_room_start_date')->getTimeStamp();
        $news_to_year = $form_state->getValue('press_room_end_date')->getTimeStamp();
        $this->removeNewsNodes($news_from_year, $news_to_year);
        $get_autocomplete = $form_state->getValue('autocomplete');
        $this->updatesitemap_deleteusasreach($get_autocomplete);
        $this->configFactory->getEditable(static::SETTINGS)
            ->set('blog_start_date', $blog_from_year)
            ->set('blog_end_date', $blog_to_year)
            ->set('press_room_start_date', $news_from_year)
            ->set('press_room_end_date', $news_to_year)
            ->set('autocomplete', $get_autocomplete)
            ->save();

        parent::submitForm($form, $form_state);
    }

	/**
	 * Remove new nodes.
	 */
    private function removeNewsNodes($news_from_year, $news_to_year)
    {
        $nids = $this->getNodesType('press_room', $news_from_year, $news_to_year);
        $this->updatesitemap_deleteusasreach($nids);
    }

	/**
	 * Remove blog nodes.
	 */
    private function removeBLogNodes($blog_from_year, $blog_to_year)
    {
        $nids = $this->getNodesType('blog_post', $blog_from_year, $blog_to_year);
        $this->updatesitemap_deleteusasreach($nids);
    }
    
	/**
	 * Get node type.
	 */    
    private function getNodesType($node_name, $node_from_year, $node_to_year)
    {
        $query = \Drupal::entityQuery('node')
            ->condition('type', $node_name)
            ->condition('created', $node_from_year, '>')
            ->condition('created', $node_to_year, '<');
        $nids  = $query->execute();
        return $nids;
    }
    
    private function updatesitemap_deleteusasreach($nids)
    {
        if (isset($nids)) {
            $nidlog = [];
            foreach ($nids as $nodenid) {
                $nidlog[] = $nodenid;
                db_update('xmlsitemap')
                    ->fields([
                        'status' => 0,
                        'access' => 0,
                        'status_override' => 1,
                    ])
                    ->condition('id', $nodenid)
                    ->execute();

                db_delete('digitalgovsearch')
                    ->condition('nid', $nodenid)
                    ->execute();
            }
            \Drupal::logger('tsa_ageout_nodes')->notice('<pre>' . 'nidlog<br />' . print_r($nidlog, TRUE) . '</pre>');
        }
    }

	/**
	 * Implements remove target id().
	 */    
    private function RemoveTartgetId($nodes)
    {
        if ($nodes) {
            foreach ($nodes as $value) {
                $node[] = $value['target_id'];
            }
            return $node;
        }
        return [];
    }
}
