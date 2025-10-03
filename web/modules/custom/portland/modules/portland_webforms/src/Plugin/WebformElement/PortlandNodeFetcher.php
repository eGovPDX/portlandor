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

    $form['node_alias_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node alias path'),
      '#default_value' => array_key_exists('#node_alias_path', $element) ? $element['#node_alias_path'] : '',
      '#description' => $this->t('Enter a path like /about-us.'),
      '#required' => TRUE,
    ];

    // Add hyperlink if node_alias_path is populated and is a valid path.
    $alias = array_key_exists('#node_alias_path', $element) ? $element['#node_alias_path'] : '';
    if (!empty($alias)) {
      // Use Drupal's Url class to build the link.
      $url = \Drupal\Core\Url::fromUserInput($alias)->toString();
      $form['node_alias_link'] = [
        '#type' => 'item',
        '#markup' => $this->t('Current node link: <a href=":url" target="_blank">:url</a>', [':url' => $url]),
      ];
    }

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
   *
   * Keep Webform's own pre_render (wrapper & data-attrs), add our classes,
   * and add an after_build to mirror #states onto the inner content.
   */
  public function getInfo(): array
  {
    $info = parent::getInfo();                         // preserve Webform’s wiring
    $info['#input'] = FALSE;                           // display-only element
    $info['#theme_wrappers'] = ['webform_element'];    // standard webform wrapper
    $info['#pre_render'][] = [static::class, 'preRenderNodeFetcher'];
    $info['#after_build'][] = [static::class, 'afterBuildPropagateStatesToContent'];
    return $info;
  }

  /**
   * Add recognizable classes to the element wrapper (useful for theming/debug).
   */
  public static function preRenderNodeFetcher(array $element): array
  {
    $element['#wrapper_attributes']['class'][] = 'js-webform-type-portland-node-fetcher';
    $element['#wrapper_attributes']['class'][] = 'webform-type-portland-node-fetcher';

    // Ensure a webform element id attribute exists for states targeting.
    if (!isset($element['#wrapper_attributes']['data-webform-element-id']) && isset($element['#webform_key'])) {
      $element['#wrapper_attributes']['data-webform-element-id'] = $element['#webform_key'];
    }

    return $element;
  }

  /**
   * After build: ensure any #states/Conditions applied to this element
   * are also applied to the inner 'content' child so it hides reliably.
   */
  public static function afterBuildPropagateStatesToContent(array $element, FormStateInterface $form_state): array
  {
    if (isset($element['#states']) && isset($element['content']) && is_array($element['content'])) {
      if (!isset($element['content']['#states'])) {
        $element['content']['#states'] = $element['#states'];
      } else {
        $element['content']['#states'] += $element['#states'];
      }
    }
    return $element;
  }

  public function buildMissingContentWarning($alias, $element)
  {
    if (\Drupal::currentUser()->isAuthenticated() && !empty($alias)) {
      return '<div class="error alert alert-danger p-3 mb-4"><p><strong>Missing content:&nbsp;</strong> <a href="' . htmlspecialchars($alias, ENT_QUOTES, 'UTF-8') . '" target="_blank">' . htmlspecialchars($element['#title'] ?? 'Node content', ENT_QUOTES, 'UTF-8') . '</a></p></div>';
    } else {
      return '<div class="error alert alert-danger p-3 mb-4"><p><strong>Missing content:&nbsp;</strong> ' . htmlspecialchars($element['#title'] ?? 'Node content', ENT_QUOTES, 'UTF-8') . '</p></div>';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL)
  {

    // Always define $edit_link to avoid undefined variable warnings.
    $edit_link = '';

    if (!isset($element['#render_inline'])) {
      $element['#render_inline'] = '1';
    }

    $alias = array_key_exists('#node_alias_path', $element) ? $element['#node_alias_path'] : '';
    $render_inline = (array_key_exists('#render_inline', $element) && $element['#render_inline'] == '1') ? '1' : '0';
    $element_name = $element['#webform_key'] ?? NULL;

    // Resolve redirect if one exists for this alias.
    $resolved_path = $alias;
    if (!empty($alias)) {
      // Remove leading slash for redirect.repository lookup.
      $alias_trimmed = ltrim($alias, '/');
      $redirects = \Drupal::service('redirect.repository')->findBySourcePath($alias_trimmed);
      if (!empty($redirects)) {
        $redirect = reset($redirects);
        if ($redirect && method_exists($redirect, 'getRedirect')) {
          $redirect_url = $redirect->getRedirect();
          // Handle internal:/node/12345 and similar URIs
          if (is_array($redirect_url) && isset($redirect_url['uri'])) {
            $uri = $redirect_url['uri'];
            if (strpos($uri, 'internal:/') === 0) {
              // Convert internal:/node/12345 to /node/12345
              $resolved_path = substr($uri, strlen('internal:'));
            } else {
              // Fallback: use the URI as-is (could be external: or route:)
              $resolved_path = $uri;
            }
          }
        }
      }
    }

    $node = NULL;
    $is_published = false;
    $value = '';

    // Use the resolved path for node lookup.
    if ($resolved_path && preg_match('/^\/node\/(\d+)$/', \Drupal::service('path_alias.manager')->getPathByAlias($resolved_path), $matches)) {
      $path_exists = 1;
      $nid = $matches[1];
      $node = Node::load($nid);

      // Get the current content language of the form/page.
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

      if ($node instanceof Node && $node->hasTranslation($language)) {
        $node = $node->getTranslation($language);
      }

      $is_published = $node instanceof Node && $node->isPublished() && $node->hasField('field_body_content') && !$node->get('field_body_content')->isEmpty();
      if ($is_published) {
        $value = $node->get('field_body_content')->value;
        // Add edit link for authenticated users if node is found and published.
        $current_user = \Drupal::currentUser();
        if ($current_user->isAuthenticated() && array_intersect(['glossary_editor', 'administrator'], $current_user->getRoles())) {
          $edit_url = $node->toUrl('edit-form')->toString();
          $node_title = $node->getTitle();
          $edit_title = 'Edit ' . $node_title;
          $edit_link = '<div class="portland-node-fetcher__edit-link"><a href="' . htmlspecialchars($edit_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="contextual-icon-link" title="' . htmlspecialchars($edit_title, ENT_QUOTES, 'UTF-8') . '">✎</a></div>';
        }
      } else {
        $error = 1;
        $value = $this->buildMissingContentWarning($resolved_path, $element);
      }
    } else {
      $error = 1;
      $value = $this->buildMissingContentWarning($resolved_path ?? "[Alias missing]", $element);
    }

    // For computed twig/data array, group edit link and content in a parent div for authenticated users.
    if ($webform_submission && $element_name && $value) {
      $grouped_markup = '<div class="portland-node-fetcher__wrapper">' . $edit_link . '<div class="portland-node-fetcher__inner-content">' . $value . '</div></div>';
      $webform_submission->setData([
        $element_name => $grouped_markup
      ] + $webform_submission->getData());
    }

    // Render content INSIDE the webform_element wrapper so #states/Conditions can hide it.
    if ($render_inline === '1' && $value) {
      $element['content'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['portland-node-fetcher__content']],
        'edit_link' => [
          '#markup' => $edit_link,
          '#weight' => 0,
        ],
        'markup' => [
          '#markup' => Markup::create('<div class="portland-node-fetcher__inner-content">' . $value . '</div>'),
          '#weight' => 10,
        ],
      ];
    } else {
      // Ensure no stray child if not rendering inline.
      unset($element['content']);
    }

    parent::prepare($element, $webform_submission);
  }
}
