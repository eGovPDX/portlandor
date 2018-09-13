<?php
/**
 * @file
 * Contains \portland\Plugin\Block\SearchBlock
 */

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_page\Plugin\Block\SearchApiPageBlock;

/**
 * Provides an abstract Search Block class.
 *
 *  @Block(
 *   id = "portland_search_block",
 *   admin_label = @Translation("Search Api Page search block form with Portland style"),
 *   category = @Translation("Forms")
 *  )
 */

class SearchBlock extends SearchApiPageBlock
{

    /**
     * (@inheritdoc)
     */
    public function blockForm($form, FormStateInterface $form_state)
    {
        $form = parent::blockForm($form, $form_state);

        $options = [
            'big' => 'Big',
            'small' => 'Small',
        ];

        $form['size'] = [
            '#type' => 'select',
            '#title' => 'Choose which size search',
            '#options' => $options,
            '#default_value' => !empty($this->configuration['size']) ? $this->configuration['size'] : 'big',
            '#required' => true,
        ];

        return $form;
    }

    /**
     * (@inheritdoc)
     */
    public function blockSubmit($form, FormStateInterface $form_state)
    {
        parent::blockSubmit($form, $form_state);
        $this->configuration['size'] = $form_state->getValue('size');
    }

    /**
     * (@inheritdoc)
     */
    public function build()
    {
        $render = parent::build();

        $render['box'] = [
            '#theme' => 'portland_search_form',
            '#size' => $this->configuration['size'],
            '#input' => $render['keys'],
            '#buttons' => $render['actions']['submit'],
        ];

        unset($render['keys'], $render['actions']);

        return $render;
    }

}
