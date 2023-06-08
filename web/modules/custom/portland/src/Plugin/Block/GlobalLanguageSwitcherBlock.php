<?php

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'portland global language switcher' block.
 *
 * @Block(
 *   id = "portland_global_language_switcher_block",
 *   admin_label = @Translation("Portland Global Language Switcher"),
 * )
 */
class GlobalLanguageSwitcherBlock extends BlockBase implements ContainerFactoryPluginInterface {
  private LanguageManagerInterface $languageManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    // get the current full URL and remove any langcode from the path.
    // we always want to translate *from* English for the best accuracy.
    $current_url = \Drupal::request()->getSchemeAndHttpHost() . preg_replace("/^\/" . $current_langcode . "(\/|$)/", "$1", \Drupal::request()->getRequestUri());
    $languages = array_map(fn ($lang) => [
      'id' => $lang->getId(),
      'label' => $lang->getName(),
      'url' => "https://translate.google.com/translate?sl=en&tl={$lang->getId()}&u={$current_url}",
    ], $this->languageManager->getNativeLanguages());

    // Chuukese is not supported by Google Translate
    unset($languages['chk']);

    return [
      '#theme' => 'portland_global_language_switcher_block',
      '#current_langcode' => $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId(),
      '#google_widget' => \Drupal::request()->query->get('google_widget'),
      '#languages' => $languages,
      '#cache' => [
        'contexts' => ['url.query_args'],
      ],
    ];
  }
}
