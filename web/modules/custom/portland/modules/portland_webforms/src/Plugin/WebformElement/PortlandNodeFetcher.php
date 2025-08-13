<?php

namespace Drupal\portland_webforms\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Render\Markup;

/**
 * Provides a 'portland_node_fetcher' webform element.
 *
 * @WebformElement(
 *   id = "portland_node_fetcher",
 *   label = @Translation("Portland Node Fetcher"),
 *   description = @Translation("Displays a configured node alias path."),
 *   category = @Translation("Custom")
 * )
 */
class PortlandNodeFetcher extends WebformElementBase
{

    /**
     * {@inheritdoc}
     */
    protected function defineDefaultProperties(): array
    {
        return [
            'node_alias_path' => '',
            'render_inline' => '1',
        ] + parent::defineDefaultProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildConfigurationForm($form, $form_state);
        $element = $form_state->get('element');

        // ✅ Pull the previously saved config from the full element array
        $form['node_alias_path'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Node alias path'),
            '#default_value' => array_key_exists('#node_alias_path', $element) ? $element['#node_alias_path'] : "",
            '#description' => $this->t('Enter a path like /about-us.'),
            '#required' => TRUE,
        ];
        $form['render_inline'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Render node content inline'),
            '#description' => $this->t('If checked, the fetched node\'s content will be rendered directly in the webform. If unchecked, it will only be available to Computed Twig elements.'),
            '#default_value' => array_key_exists('#render_inline', $element) ? $element['#render_inline'] : TRUE,
        ];
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void
    {
        parent::submitConfigurationForm($form, $form_state);

        $user_input = $form_state->getUserInput();
        if (isset($user_input['node_alias_path'])) {
            $form_state->setValue('node_alias_path', $user_input['node_alias_path']);
        }
        $render_inline = $user_input['render_inline'] === NULL ? '0' : $user_input['render_inline'];
        $form_state->setValue('render_inline', $render_inline);
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(): array
    {
        return [
            '#input' => FALSE,
            '#markup' => '',
            '#theme_wrappers' => ['container'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL)
    {
        if (!isset($element['#render_inline'])) {
            $element['#render_inline'] = "1"; // or your desired default
        }

        $alias = array_key_exists('#node_alias_path', $element) ? $element['#node_alias_path'] : "";
        $render_inline = array_key_exists('#render_inline', $element) && $element['#render_inline'] == "1" ? $element['#render_inline'] : "0";
        $element_name = $element['#webform_key'] ?? NULL;

        if ($alias) {
            $internal_path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
            if (preg_match('/^\/node\/(\d+)$/', $internal_path, $matches)) {
                $nid = $matches[1];
                $node = Node::load($nid);
                if ($node instanceof Node) {
                    // Attach the node entity to the element for debugging or internal use.
                    $element['#node'] = $node;

                    // Flatten all field values and add them to temporary data.
                    $values = [];
                    foreach ($node->getFields() as $field_name => $field) {
                        if ($field->count() === 1) {
                            $values[$field_name] = $field->value;
                        } else {
                            $values[$field_name] = [];
                            foreach ($field as $item) {
                                $values[$field_name][] = $item->value;
                            }
                        }
                    }

                    $values['node_alias_path'] = $alias;

                    // Add to submission data, available in Twig via `data.fetch_node`.
                    if ($webform_submission) {
                        $webform_submission->setData([$element_name => $values] + $webform_submission->getData());
                    }
                }
            }
        }

        // Conditionally render the node body content as HTML if render_inline is enabled
        if ($render_inline && isset($node) && $node->hasField('field_body_content')) {
            $html = $node->get('field_body_content')->value; // or ->processed if you want filtered text
            $element['#markup'] = Markup::create($html);
        } else {
            // If not rendering inline, or no node/field, set empty markup to avoid errors.
            $element['#markup'] = Markup::create('');
        }

        parent::prepare($element, $webform_submission);
    }
}
