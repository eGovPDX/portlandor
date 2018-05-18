<?php

namespace Drupal\twig_tweak;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Twig extension with some useful functions and filters.
 *
 * Dependency injection is not used for performance reason.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('drupal_view', 'views_embed_view'),
      new \Twig_SimpleFunction('drupal_view_result', 'views_get_view_result'),
      new \Twig_SimpleFunction('drupal_block', [$this, 'drupalBlock']),
      new \Twig_SimpleFunction('drupal_region', [$this, 'drupalRegion']),
      new \Twig_SimpleFunction('drupal_entity', [$this, 'drupalEntity']),
      new \Twig_SimpleFunction('drupal_field', [$this, 'drupalField']),
      new \Twig_SimpleFunction('drupal_menu', [$this, 'drupalMenu']),
      new \Twig_SimpleFunction('drupal_form', [$this, 'drupalForm']),
      new \Twig_SimpleFunction('drupal_image', [$this, 'drupalImage']),
      new \Twig_SimpleFunction('drupal_token', [$this, 'drupalToken']),
      new \Twig_SimpleFunction('drupal_config', [$this, 'drupalConfig']),
      new \Twig_SimpleFunction('drupal_dump', [$this, 'drupalDump']),
      new \Twig_SimpleFunction('dd', [$this, 'drupalDump']),
      new \Twig_SimpleFunction('drupal_title', [$this, 'drupalTitle']),
      new \Twig_SimpleFunction('drupal_url', [$this, 'drupalUrl']),
      new \Twig_SimpleFunction('drupal_link', [$this, 'drupalLink']),
      new \Twig_SimpleFunction('drupal_messages', [$this, 'drupalMessages']),
      new \Twig_SimpleFunction('drupal_breadcrumb', [$this, 'drupalBreadcrumb']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    $filters = [
      new \Twig_SimpleFilter('token_replace', [$this, 'tokenReplaceFilter']),
      new \Twig_SimpleFilter('preg_replace', [$this, 'pregReplaceFilter']),
      new \Twig_SimpleFilter('image_style', [$this, 'imageStyle']),
      new \Twig_SimpleFilter('transliterate', [$this, 'transliterate']),
      new \Twig_SimpleFilter('check_markup', [$this, 'checkMarkup']),
      new \Twig_SimpleFilter('truncate', [$this, 'truncate']),
      new \Twig_SimpleFilter('view', [$this, 'view']),
      new \Twig_SimpleFilter('with', [$this, 'with']),
    ];
    // PHP filter should be enabled in settings.php file.
    if (Settings::get('twig_tweak_enable_php_filter')) {
      $filters[] = new \Twig_SimpleFilter('php', [$this, 'phpFilter']);
    }
    return $filters;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'twig_tweak';
  }

  /**
   * Builds the render array for a block.
   *
   * @param mixed $id
   *   The string of block plugin to render.
   * @param array $configuration
   *   (Optional) Pass on any configuration to the plugin block.
   * @param bool $wrapper
   *   (Optional) Whether or not use block template for rendering.
   *
   * @return null|array
   *   A render array for the block or NULL if the block cannot be rendered.
   */
  public function drupalBlock($id, array $configuration = [], $wrapper = TRUE) {

    $configuration += ['label_display' => BlockPluginInterface::BLOCK_LABEL_VISIBLE];

    /** @var \Drupal\Core\Block\BlockPluginInterface $block_plugin */
    $block_plugin = \Drupal::service('plugin.manager.block')
      ->createInstance($id, $configuration);

    // Inject runtime contexts.
    if ($block_plugin instanceof ContextAwarePluginInterface) {
      $contexts = \Drupal::service('context.repository')->getRuntimeContexts($block_plugin->getContextMapping());
      \Drupal::service('context.handler')->applyContextMapping($block_plugin, $contexts);
    }

    if (!$block_plugin->access(\Drupal::currentUser())) {
      return;
    }

    $content = $block_plugin->build();

    if ($content && !Element::isEmpty($content)) {
      if ($wrapper) {
        $build = [
          '#theme' => 'block',
          '#attributes' => [],
          '#contextual_links' => [],
          '#configuration' => $block_plugin->getConfiguration(),
          '#plugin_id' => $block_plugin->getPluginId(),
          '#base_plugin_id' => $block_plugin->getBaseId(),
          '#derivative_plugin_id' => $block_plugin->getDerivativeId(),
          'content' => $content,
        ];
      }
      else {
        $build = $content;
      }
    }
    else {
      // Preserve cache metadata of empty blocks.
      $build = [
        '#markup' => '',
        '#cache' => $content['#cache'],
      ];
    }

    if (!empty($content)) {
      CacheableMetadata::createFromRenderArray($build)
        ->merge(CacheableMetadata::createFromRenderArray($content))
        ->applyTo($build);
    }

    return $build;
  }

  /**
   * Builds the render array of a given region.
   *
   * @param string $region
   *   The region to build.
   * @param string $theme
   *   (Optional) The name of the theme to load the region. If it is not
   *   provided then default theme will be used.
   *
   * @return array
   *   A render array to display the region content.
   */
  public function drupalRegion($region, $theme = NULL) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $blocks = $entity_type_manager->getStorage('block')->loadByProperties([
      'region' => $region,
      'theme'  => $theme ?: \Drupal::config('system.theme')->get('default'),
    ]);

    $view_builder = $entity_type_manager->getViewBuilder('block');

    $build = [];

    /* @var $blocks \Drupal\block\BlockInterface[] */
    foreach ($blocks as $id => $block) {
      if ($block->access('view')) {
        $block_plugin = $block->getPlugin();
        if ($block_plugin instanceof TitleBlockPluginInterface) {
          $request = \Drupal::request();
          if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
            $block_plugin->setTitle(\Drupal::service('title_resolver')->getTitle($request, $route));
          }
        }
        $build[$id] = $view_builder->view($block);
      }
    }

    if ($build) {
      $build['#region'] = $region;
      $build['#theme_wrappers'] = ['region'];
    }

    return $build;
  }

  /**
   * Returns the render array for an entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param mixed $id
   *   The ID of the entity to build.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   * @param bool $check_access
   *   (Optional) Indicates that access check is required.
   *
   * @return null|array
   *   A render array for the entity or NULL if the entity does not exist.
   */
  public function drupalEntity($entity_type, $id = NULL, $view_mode = NULL, $langcode = NULL, $check_access = TRUE) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity = $id
      ? $entity_type_manager->getStorage($entity_type)->load($id)
      : \Drupal::routeMatch()->getParameter($entity_type);
    if ($entity && (!$check_access || $entity->access('view'))) {
      $render_controller = $entity_type_manager->getViewBuilder($entity_type);
      return $render_controller->view($entity, $view_mode, $langcode);
    }
  }

  /**
   * Returns the render array for a single entity field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $entity_type
   *   The entity type.
   * @param mixed $id
   *   The ID of the entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the field.
   * @param string $langcode
   *   (optional) Language code to load translation.
   * @param bool $check_access
   *   (Optional) Indicates that access check is required.
   *
   * @return null|array
   *   A render array for the field or NULL if the value does not exist.
   */
  public function drupalField($field_name, $entity_type, $id = NULL, $view_mode = 'default', $langcode = NULL, $check_access = TRUE) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $id
      ? \Drupal::entityTypeManager()->getStorage($entity_type)->load($id)
      : \Drupal::routeMatch()->getParameter($entity_type);
    if ($entity && (!$check_access || $entity->access('view'))) {
      $entity = \Drupal::service('entity.repository')
        ->getTranslationFromContext($entity, $langcode);
      if (isset($entity->{$field_name})) {
        return $entity->{$field_name}->view($view_mode);
      }
    }
  }

  /**
   * Returns the render array for Drupal menu.
   *
   * @param string $menu_name
   *   The name of the menu.
   * @param int $level
   *   (optional) Initial menu level.
   * @param int $depth
   *   (optional) Maximum number of menu levels to display.
   * @param bool $expand
   *   (optional) Expand all menu links.
   *
   * @return array
   *   A render array for the menu.
   */
  public function drupalMenu($menu_name, $level = 1, $depth = 0, $expand = FALSE) {
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree */
    $menu_tree = \Drupal::service('menu.link_tree');
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);

    // Adjust the menu tree parameters based on the block's configuration.
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $menu_tree->maxDepth()));
    }

    // If expandedParents is empty, the whole menu tree is built.
    if ($expand) {
      $parameters->expandedParents = [];
    }

    $tree = $menu_tree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    return $menu_tree->build($tree);
  }

  /**
   * Builds and processes a form for a given form ID.
   *
   * @param string $form_id
   *   The form ID.
   * @param ...
   *   Additional arguments are passed to form constructor.
   *
   * @return array
   *   A render array to represent the form.
   */
  public function drupalForm($form_id) {
    $form_builder = \Drupal::formBuilder();
    return call_user_func_array([$form_builder, 'getForm'], func_get_args());
  }

  /**
   * Builds an image.
   *
   * @param mixed $property
   *   A property to identify the image.
   * @param string $style
   *   (Optional) Image style.
   * @param array $attributes
   *   (Optional) Image attributes.
   * @param bool $responsive
   *   (Optional) Indicates that the provided image style is responsive.
   * @param bool $check_access
   *   (Optional) Indicates that access check is required.
   *
   * @return array|null
   *   A render array to represent the image.
   */
  public function drupalImage($property, $style = NULL, array $attributes = [], $responsive = FALSE, $check_access = TRUE) {

    // Determine property type by its value.
    if (preg_match('/^\d+$/', $property)) {
      $property_type = 'fid';
    }
    elseif (Uuid::isValid($property)) {
      $property_type = 'uuid';
    }
    else {
      $property_type = 'uri';
    }

    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties([$property_type => $property]);

    // To avoid ambiguity render nothing unless exact one image was found.
    if (count($files) != 1) {
      return;
    }

    $file = reset($files);

    if ($check_access && !$file->access('view')) {
      return;
    }

    $build = [
      '#uri' => $file->getFileUri(),
      '#attributes' => $attributes,
    ];

    if ($style) {
      if ($responsive) {
        $build['#type'] = 'responsive_image';
        $build['#responsive_image_style_id'] = $style;
      }
      else {
        $build['#theme'] = 'image_style';
        $build['#style_name'] = $style;
      }
    }
    else {
      $build['#theme'] = 'image';
    }

    return $build;
  }

  /**
   * Replaces a given tokens with appropriate value.
   *
   * @param string $token
   *   A replaceable token.
   * @param array $data
   *   (optional) An array of keyed objects. For simple replacement scenarios
   *   'node', 'user', and others are common keys, with an accompanying node or
   *   user object being the value. Some token types, like 'site', do not
   *   require any explicit information from $data and can be replaced even if
   *   it is empty.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the token
   *   replacement process.
   *
   * @return string
   *   The token value.
   *
   * @see \Drupal\Core\Utility\Token::replace()
   */
  public function drupalToken($token, array $data = [], array $options = []) {
    return \Drupal::token()->replace("[$token]", $data, $options);
  }

  /**
   * Gets data from this configuration.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   * @param string $key
   *   A string that maps to a key within the configuration data.
   *
   * @return mixed
   *   The data that was requested.
   */
  public function drupalConfig($name, $key) {
    return \Drupal::config($name)->get($key);
  }

  /**
   * Dumps information about variables.
   *
   * @param mixed $var
   *   The variable to dump.
   */
  public function drupalDump($var) {
    $var_dumper = '\Symfony\Component\VarDumper\VarDumper';
    if (class_exists($var_dumper)) {
      call_user_func($var_dumper . '::dump', $var);
    }
    else {
      trigger_error('Could not dump the variable because symfony/var-dumper component is not installed.', E_USER_WARNING);
    }
  }

  /**
   * An alias for self::drupalDump().
   *
   * @param mixed $var
   *   The variable to dump.
   *
   * @see \Drupal\twig_tweak\TwigExtension::drupalDump();
   */
  public function dd($var) {
    $this->drupalDump($var);
  }

  /**
   * Returns a title for the current route.
   *
   * @return array
   *   A render array to represent page title.
   */
  public function drupalTitle() {
    $title = \Drupal::service('title_resolver')->getTitle(
      \Drupal::request(),
      \Drupal::routeMatch()->getRouteObject()
    );
    $build['#markup'] = render($title);
    $build['#cache']['contexts'] = ['url'];
    return $build;
  }

  /**
   * Generates a URL from an internal path.
   *
   * @param string $user_input
   *   User input for a link or path.
   * @param array $options
   *   (optional) An array of options.
   * @param bool $check_access
   *   (Optional) Indicates that access check is required.
   *
   * @return \Drupal\Core\Url
   *   A new Url object based on user input.
   *
   * @see \Drupal\Core\Url::fromUserInput()
   */
  public function drupalUrl($user_input, array $options = [], $check_access = FALSE) {
    if (!in_array($user_input[0], ['/', '#', '?'])) {
      $user_input = '/' . $user_input;
    }
    $url = Url::fromUserInput($user_input, $options);
    if (!$check_access || $url->access()) {
      return $url;
    }
  }

  /**
   * Generates a link from an internal path.
   *
   * @param string $text
   *   The text to be used for the link.
   * @param string $user_input
   *   User input for a link or path.
   * @param array $options
   *   (optional) An array of options.
   * @param bool $check_access
   *   (Optional) Indicates that access check is required.
   *
   * @return \Drupal\Core\Link
   *   A new Link object.
   *
   * @see \Drupal\Core\Link::fromTextAndUrl()
   */
  public function drupalLink($text, $user_input, array $options = [], $check_access = FALSE) {
    $url = $this->drupalUrl($user_input, $options, $check_access);
    if ($url) {
      return Link::fromTextAndUrl($text, $url);
    }
  }

  /**
   * Displays status messages.
   */
  public function drupalMessages() {
    return ['#type' => 'status_messages'];
  }

  /**
   * Builds the breadcrumb.
   */
  public function drupalBreadcrumb() {
    return \Drupal::service('breadcrumb')
      ->build(\Drupal::routeMatch())
      ->toRenderable();
  }

  /**
   * Replaces all tokens in a given string with appropriate values.
   *
   * @param string $text
   *   An HTML string containing replaceable tokens.
   *
   * @return string
   *   The entered HTML text with tokens replaced.
   */
  public function tokenReplaceFilter($text) {
    return \Drupal::token()->replace($text);
  }

  /**
   * Performs a regular expression search and replace.
   *
   * @param string $text
   *   The text to search and replace.
   * @param string $pattern
   *   The pattern to search for.
   * @param string $replacement
   *   The string to replace.
   *
   * @return string
   *   The new text if matches are found, otherwise unchanged text.
   */
  public function pregReplaceFilter($text, $pattern, $replacement) {
    return preg_replace($pattern, $replacement, $text);
  }

  /**
   * Returns the URL of this image derivative for an original image path or URI.
   *
   * @param string $path
   *   The path or URI to the original image.
   * @param string $style
   *   The image style.
   *
   * @return string
   *   The absolute URL where a style image can be downloaded, suitable for use
   *   in an <img> tag. Requesting the URL will cause the image to be created.
   */
  public function imageStyle($path, $style) {
    /** @var \Drupal\Image\ImageStyleInterface $image_style */
    if ($image_style = ImageStyle::load($style)) {
      return file_url_transform_relative($image_style->buildUrl($path));
    }
  }

  /**
   * Transliterates text from Unicode to US-ASCII.
   *
   * @param string $string
   *   The string to transliterate.
   * @param string $langcode
   *   (optional) The language code of the language the string is in. Defaults
   *   to 'en' if not provided. Warning: this can be unfiltered user input.
   * @param string $unknown_character
   *   (optional) The character to substitute for characters in $string without
   *   transliterated equivalents. Defaults to '?'.
   * @param int $max_length
   *   (optional) If provided, return at most this many characters, ensuring
   *   that the transliteration does not split in the middle of an input
   *   character's transliteration.
   *
   * @return string
   *   $string with non-US-ASCII characters transliterated to US-ASCII
   *   characters, and unknown characters replaced with $unknown_character.
   */
  public function transliterate($string, $langcode = 'en', $unknown_character = '?', $max_length = NULL) {
    return \Drupal::transliteration()->transliterate($string, $langcode, $unknown_character, $max_length);
  }

  /**
   * Runs all the enabled filters on a piece of text.
   *
   * @param string $text
   *   The text to be filtered.
   * @param string|null $format_id
   *   (optional) The machine name of the filter format to be used to filter the
   *   text. Defaults to the fallback format. See filter_fallback_format().
   * @param string $langcode
   *   (optional) The language code of the text to be filtered.
   * @param array $filter_types_to_skip
   *   (optional) An array of filter types to skip, or an empty array (default)
   *   to skip no filter types.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The filtered text.
   *
   * @see check_markup()
   */
  public function checkMarkup($text, $format_id = NULL, $langcode = '', array $filter_types_to_skip = []) {
    return check_markup($text, $format_id, $langcode, $filter_types_to_skip);
  }

  /**
   * Truncates a UTF-8-encoded string safely to a number of characters.
   *
   * @param string $string
   *   The string to truncate.
   * @param int $max_length
   *   An upper limit on the returned string length, including trailing ellipsis
   *   if $add_ellipsis is TRUE.
   * @param bool $wordsafe
   *   (Optional) If TRUE, attempt to truncate on a word boundary.
   * @param bool $add_ellipsis
   *   (Optional) If TRUE, add '...' to the end of the truncated string.
   * @param int $min_wordsafe_length
   *   (Optional) If TRUE, the minimum acceptable length for truncation.
   *
   * @return string
   *   The truncated string.
   *
   * @see \Drupal\Component\Utility\Unicode::truncate()
   */
  public function truncate($string, $max_length, $wordsafe = FALSE, $add_ellipsis = FALSE, $min_wordsafe_length = 1) {
    return Unicode::truncate($string, $max_length, $wordsafe, $add_ellipsis, $min_wordsafe_length);
  }

  /**
   * Adds new element to the array.
   *
   * @param array $build
   *   The renderable array to add the child item.
   * @param int|string $key
   *   The key of the new element.
   * @param mixed $element
   *   The element to add.
   *
   * @return array
   *   The modified array.
   */
  public function with(array $build, $key, $element) {
    $build[$key] = $element;
    return $build;
  }

  /**
   * Returns a render array for entity, field list or field item.
   *
   * @param mixed $object
   *   The object to build a render array from.
   * @param string|array $display_options
   *   Can be either the name of a view mode, or an array of display settings.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   * @param bool $check_access
   *   (Optional) Indicates that access check is required.
   *
   * @return array
   *   A render array to represent the object.
   */
  public function view($object, $display_options = 'default', $langcode = NULL, $check_access = TRUE) {
    if ($object instanceof FieldItemListInterface || $object instanceof FieldItemInterface) {
      return $object->view($display_options);
    }
    elseif ($object instanceof EntityInterface && (!$check_access || $object->access('view'))) {
      return \Drupal::entityTypeManager()
        ->getViewBuilder($object->getEntityTypeId())
        ->view($object, $display_options, $langcode);
    }
  }

  /**
   * Evaluates a string of PHP code.
   *
   * @param string $code
   *   Valid PHP code to be evaluated.
   *
   * @return mixed
   *   The eval() result.
   */
  public function phpFilter($code) {
    ob_start();
    // @codingStandardsIgnoreStart
    print eval($code);
    // @codingStandardsIgnoreEnd
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }

}
