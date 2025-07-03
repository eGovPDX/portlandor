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
 *   category = @Translation("Portland elements"),
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
    public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL)
    {
        $alias = $element['#node_alias_path'] ?? NULL;

        if ($alias) {

            $internal_path = $this->resolveCanonicalPathFromAlias($alias);

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
                        $data = $webform_submission->getData();
                        $data[$element['#webform_key']] = $values;
                        $webform_submission->setData($data);
                    }
                }
            }
        }

        // Suppress output in form.
        $element['#markup'] = Markup::create('');
        // Uncomment the line below to display the node title and body content in the form for testing
        //$element['#markup'] = Markup::create('<h2>' . $values['title'] . '</h2> ' . $values['field_body_content']);

        parent::prepare($element, $webform_submission);
    }

    /**
     * Resolves a canonical path from a potentially redirected alias.
     *
     * @param string $alias
     *   The user-provided alias, like /foo or /bar/page.
     *
     * @param int $max_depth
     *   Maximum redirect hops to follow (default: 5).
     *
     * @return string
     *   The resolved canonical system path (e.g., /node/123), or the fallback path.
     */
    protected function resolveCanonicalPathFromAlias(string $alias, int $depth = 0): ?string
    {
        if ($depth > 5) {
            \Drupal::logger('portland_webforms')->warning('Redirect recursion exceeded for alias: @alias', ['@alias' => $alias]);
            return NULL;
        }

        $alias_trimmed = ltrim($alias, '/');
        $redirect = \Drupal::service('redirect.repository')->findMatchingRedirect($alias_trimmed);

        if ($redirect) {
            return $this->resolveCanonicalPathFromAlias($redirect->getRedirectDestination(), $depth + 1);
        }

        return \Drupal::service('path_alias.manager')->getPathByAlias($alias);
    }
}
