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
      'open_links_in_new_tab' => '1',
  // Default HTML snippet appended to links when opening in a new tab.
  // Contains a span with an invisible figure-space character (U+2007)
  // used to detect that the icon has already been added. The icon
  // is hidden from assistive tech via aria-hidden="true".
  'link_icon' => ' <span class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"> </span>',
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

    $form['open_links_in_new_tab'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open links in new tab'),
      '#description' => $this->t('If checked, any links in the fetched node will be modified to open in a new tab/window.'),
      '#default_value' => array_key_exists('#open_links_in_new_tab', $element) ? $element['#open_links_in_new_tab'] : TRUE,
    ];

    $form['link_icon'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Link icon (HTML)'),
      '#description' => $this->t('HTML snippet appended to link text when "Open links in new tab" is enabled. Provide a small <span> element. Default: the external link icon.'),
      '#default_value' => array_key_exists('#link_icon', $element) ? $element['#link_icon'] : ' <span class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"> </span>',
      '#rows' => 2,
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
    // Persist open_links_in_new_tab checkbox value.
    $open_links = $user_input['open_links_in_new_tab'] === NULL ? '0' : $user_input['open_links_in_new_tab'];
    $form_state->setValue('open_links_in_new_tab', $open_links);
    // Persist configured icon HTML.
    $link_icon = isset($user_input['link_icon']) ? $user_input['link_icon'] : '';
    $form_state->setValue('link_icon', $link_icon);
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

    // Ensure open_links_in_new_tab has a sensible default when the webform
    // element config doesn't include it (older webforms or unset checkboxes).
    if (!isset($element['#open_links_in_new_tab'])) {
      $element['#open_links_in_new_tab'] = '1';
    }

    // Ensure link_icon has a sensible default when missing from saved config.
    if (!isset($element['#link_icon'])) {
      $element['#link_icon'] = ' <span class="fa-solid fa-arrow-up-right-from-square"> </span>';
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

      // Get the current content language of the form/page and use it for
      // translation and caching to avoid language collisions.
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

      if ($node instanceof Node && $node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
      }

      // Attach render cache metadata as soon as we know the node id and
      // content language so both successful and error/warning states vary
      // correctly and are invalidated when the node changes.
      $element['#cache']['tags'][] = 'node:' . $nid;
      $element['#cache']['contexts'][] = 'languages:language_content';
      $element['#cache']['contexts'][] = 'user.roles';

      $is_published = $node instanceof Node && $node->isPublished() && $node->hasField('field_body_content') && !$node->get('field_body_content')->isEmpty();
      if ($is_published) {
        $value = $node->get('field_body_content')->processed;
        // If configured, ensure links open in a new tab/window for safety add rel attributes.
        $open_links_enabled = (array_key_exists('#open_links_in_new_tab', $element) && $element['#open_links_in_new_tab'] == '1') ? TRUE : FALSE;
        if ($open_links_enabled && !empty($value)) {
          // Fast path: only proceed if there's an <a> tag that contains an
          // href attribute (attributes may appear in any order). This avoids
          // DOM parsing for content that has anchors without hrefs or no
          // anchors at all.
          // If there's no <a> with an href attribute, we intentionally skip
          // all link-rewrite work (no cache lookup, no DOM parsing, no cache
          // write). This is an early-exit fast-path to avoid unnecessary CPU
          // work for content without actionable links.
          if (!preg_match('/<a\b[^>]*\bhref\s*=\s*/i', $value)) {
            // TEMP LOG: record that we skipped processing due to no href
            // anchors. Remove or lower the log level once measurement is done.
            \Drupal::logger('portland_node_fetcher')->notice('Skipped link processing (no <a href>) for nid {nid} lang {lang}', ['nid' => $nid, 'lang' => $langcode]);
          
          }
          else {
            // Use Drupal cache to avoid reparsing HTML for the same node repeatedly.
            // Include language in the cache id to avoid collisions between
            // translated bodies.
            $cid = 'portland_node_fetcher:processed_links:' . $nid . ':' . $langcode . ':' . ($open_links_enabled ? '1' : '0');
            $cache = \Drupal::cache('data')->get($cid);
            if ($cache) {
              $value = $cache->data;
              // Cache hit: no logging to avoid noise (misses and skips are logged).
            }
            else {
              // TEMP LOG: cache miss — we'll parse and then store the result.
              \Drupal::logger('portland_node_fetcher')->notice('Cache MISS for processed links: {cid} (nid {nid} lang {lang})', ['cid' => $cid, 'nid' => $nid, 'lang' => $langcode]);
              
              // Use DOMDocument to safely update anchor tags. Suppress warnings for malformed HTML.
              libxml_use_internal_errors(true);
              $doc = new \DOMDocument();
              // Load wrapped HTML to ensure a single root element.
              $loaded = $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $value . '</div>');
              if ($loaded) {
                $xpath = new \DOMXPath($doc);
                $anchors = $xpath->query('//a');
                foreach ($anchors as $a) {
                  if ($a instanceof \DOMElement) {
                    // Set target="_blank"
                    $a->setAttribute('target', '_blank');
                    // Merge or set rel attribute to include noopener noreferrer
                    $existing_rel = $a->getAttribute('rel');
                    $rels = preg_split('/\s+/', trim($existing_rel)) ?: [];
                    foreach (['noopener', 'noreferrer'] as $required) {
                      if (!in_array($required, $rels)) {
                        $rels[] = $required;
                      }
                    }
                    $a->setAttribute('rel', trim(implode(' ', array_filter($rels))));
                    // Append the external-link icon HTML at the end of the anchor
                    // only if a span containing the special invisible character
                    // is not already present. The invisible character used is
                    // U+2007 (figure space) and is included inside the icon HTML
                    // by default. The icon HTML is configurable via the
                    // element configuration (see the Link icon textarea).
                    $invisible_char = ' ';
                    $has_icon_span = FALSE;

                    // 1) Invisible character check (existing behavior).
                    $span_nodes = $a->getElementsByTagName('span');
                    foreach ($span_nodes as $snode) {
                      if ($snode instanceof \DOMElement && strpos($snode->textContent, $invisible_char) !== FALSE) {
                        $has_icon_span = TRUE;
                        break;
                      }
                    }

                    // 2) Exact-fragment substring check against configured icon HTML.
                    $icon_html = trim($element['#link_icon'] ?? ' <span class="fa-solid fa-arrow-up-right-from-square"> </span>');
                    if (!$has_icon_span && $icon_html !== '') {
                      // Use saveHTML on the anchor to get a string representation
                      // and look for the configured fragment. This is a best-effort
                      // string match and helps avoid duplicating identical HTML.
                      $anchor_html = $doc->saveHTML($a);
                      if (strpos($anchor_html, $icon_html) !== FALSE) {
                        $has_icon_span = TRUE;
                      }
                    }

                    // 3) Class-based detection: if configured icon contains a class
                    // token, consider the icon present when an inner span already
                    // has any of those tokens.
                    if (!$has_icon_span && preg_match('/class=["\']([^"\']+)["\']/', $icon_html, $cmatch)) {
                      $classes = preg_split('/\s+/', trim($cmatch[1]));
                      if (!empty($classes)) {
                        foreach ($span_nodes as $snode) {
                          if ($snode instanceof \DOMElement) {
                            $span_classes = preg_split('/\s+/', trim($snode->getAttribute('class')));
                            if (array_intersect($classes, $span_classes)) {
                              $has_icon_span = TRUE;
                              break;
                            }
                          }
                        }
                      }
                    }

                    if (!$has_icon_span) {
                      // Add a normal space before the icon for separation.
                      $a->appendChild($doc->createTextNode(' '));
                      // Try to insert the configured HTML as a fragment. If the
                      // fragment isn't valid XML/HTML, fall back to creating a
                      // simple span element with the default class and invisible
                      // character.
                      $frag = $doc->createDocumentFragment();
                      $ok = FALSE;
                      // Suppress warnings from malformed fragments.
                      try {
                        $ok = @$frag->appendXML($icon_html);
                      } catch (\Throwable $e) {
                        $ok = FALSE;
                      }
                      if ($ok !== FALSE && $ok !== NULL) {
                        $a->appendChild($frag);
                      } else {
                        // Fallback: attempt to extract a class attribute from
                        // the configured HTML, otherwise use the default class.
                        $class_attr = 'fa-solid fa-arrow-up-right-from-square';
                        if (preg_match('/class=["\']([^"\']+)["\']/', $icon_html, $cmatch)) {
                          $class_attr = $cmatch[1];
                        }
                        $fallback_span = $doc->createElement('span', $invisible_char);
                        $fallback_span->setAttribute('class', $class_attr);
                        // Ensure the icon is hidden from assistive tech.
                        $fallback_span->setAttribute('aria-hidden', 'true');
                        $a->appendChild($fallback_span);
                      }
                    }
                  }
                }
                // Extract innerHTML of our wrapper div.
                $body_div = $doc->getElementsByTagName('div')->item(0);
                $inner_html = '';
                if ($body_div) {
                  foreach ($body_div->childNodes as $child) {
                    $inner_html .= $doc->saveHTML($child);
                  }
                  $value = $inner_html;
                }
              }
              libxml_clear_errors();

              // Cache the processed HTML in the data cache bin and tag by node
              // so it's invalidated on node updates.
              \Drupal::cache('data')->set($cid, $value, \Drupal\Core\Cache\Cache::PERMANENT, ['node:' . $nid]);
            }
          }
        }
        // (Cache metadata attached earlier.)
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
      // Ensure render cache contexts are present even when alias did not
      // resolve to a node so warning output varies by language and user
      // roles and cannot leak privileged UI into anonymous caches.
      $element['#cache']['contexts'][] = 'languages:language_content';
      $element['#cache']['contexts'][] = 'user.roles';
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
