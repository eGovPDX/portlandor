<?php
/**
 * @file
 * Contains \Drupal\bootstrap_library\BootstrapLibrarySettingsForm
 */
namespace Drupal\bootstrap_library;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure bootstrap_library settings for this site.
 */
class BootstrapLibrarySettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'bootstrap_library_admin_settings';
  }
  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bootstrap_library.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bootstrap_library.settings');
	
    $themes = \Drupal::service('theme_handler')->listInfo();
    $active_themes = array();
    foreach ($themes as $key => $theme) {
      if ($theme->status) {
        $active_themes[$key] = $theme->info['name'];
      }
    }
    // Load from CDN
    $form['cdn'] = array(
      '#type' => 'fieldset',
      '#title' => t('Load Boostrap from CDN'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
	$data = _bootstrap_library_data();
    $cdn_options =  json_decode( $data );
    $versions = array_keys(_bootstrap_library_object_to_array($cdn_options->bootstrap));
    $options = array_combine($versions, $versions);
    array_unshift( $options, 'Load locally' );
    $form['cdn']['bootstrap'] = array(
      '#type' => 'select',
      '#title' => t('Select Bootstrap version to load via CDN, non for local library'),
      '#options' => $options,
      '#default_value' => $config->get('cdn.bootstrap'),
    );
    $form['cdn']['cdn_options'] = array(
      '#type' => 'hidden', 
	  '#value' => $data
	);
	// Production or minimized version
    $form['minimized'] = array(
      '#type' => 'fieldset',
      '#title' => t('Minimized, Non-minimized, or Composer version'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['minimized']['minimized_options'] = array(
      '#type' => 'radios',
      '#title' => t('Choose minimized, non-minimized, or composer version.'),
      '#options' => array(
        0 => t('Use non minimized libraries (Development)'),
        1 => t('Use minimized libraries (Production)'),
        2 => t('Use composer installed libraries'),      ),
      '#default_value' => $config->get('minimized.options'),
    );
    // Per-theme visibility.
    $form['theme'] = array(
      '#type' => 'fieldset',
      '#title' => t('Themes Visibility'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['theme']['theme_visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Activate on specific themes'),
      '#options' => array(
        0 => t('All themes except those listed'),
        1 => t('Only the listed themes'),
      ),
      '#default_value' => $config->get('theme.visibility'),
    );
    $form['theme']['theme_themes'] = array(
      '#type' => 'select',
      '#title' => 'List of themes where library will be loaded.',
      '#options' => $active_themes,
      '#multiple' => TRUE,
      '#default_value' => $config->get('theme.themes'),
      '#description' => t("Specify in which themes you wish the library to load."),
    );
    // Per-path visibility.
    $form['url'] = array(
      '#type' => 'fieldset',
      '#title' => t('Activate on specific URLs'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['url']['url_visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Load bootstrap on specific pages'),
      '#options' => array(0 => t('All pages except those listed'), 1 => t('Only the listed pages')),
      '#default_value' => $config->get('url.visibility'),
    );
    $form['url']['url_pages'] = array(
      '#type' => 'textarea',
      '#title' => '<span class="element-invisible">' . t('Pages') . '</span>',
      '#default_value' => _bootstrap_library_array_to_string($config->get('url.pages')),
      '#description' => t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>')),
    );

    // Files settings.
    $form['files'] = array(
      '#type' => 'fieldset',
      '#title' => t('Files Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['files']['types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Select which files to load from the library. By default you should check both, but in some cases you will need to load only CSS or JS Bootstrap files.'),
      '#options' => array(
        'css' => t('CSS files'),
        'js' => t('Javascript files'),
      ),
      '#default_value' => $config->get('files.types'),
    );

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('bootstrap_library.settings')
      ->set('theme.visibility', $form_state->getValue('theme_visibility'))
      ->set('theme.themes', $form_state->getValue('theme_themes'))
      ->set('url.visibility', $form_state->getValue('url_visibility'))
      ->set('url.pages', _bootstrap_library_string_to_array($form_state->getValue('url_pages')))
      ->set('minimized.options', $form_state->getValue('minimized_options'))
      ->set('cdn.bootstrap', $form_state->getValue('bootstrap'))
      ->set('cdn.options', $form_state->getValue('cdn_options'))
      ->set('files.types', $form_state->getValue('types'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}

/**
 * Replaces CDN version options.
 * This is while find uptodate solution for CDN Version options
 */
function _bootstrap_library_data() {

  return '{
  "timestamp": "2015-11-09T18:54:50.335Z",
  "bootstrap": {
    "4.1.1": {
      "css": "//stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css",
      "js": [
        "//cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js",
        "//stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"
      ]
    },
    "4.0.0": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css",
      "js": [
        "//cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js",
        "//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
      ]
    },
    "3.3.7": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
    },
    "3.3.6": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"
    },
    "3.3.5": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"
    },
    "3.3.4": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"
    },
    "3.3.3": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.3/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.3/js/bootstrap.min.js"
    },
    "3.3.2": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"
    },
    "3.3.1": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"
    },
    "3.3.0": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"
    },
    "3.2.0": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"
    },
    "3.1.1": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"
    },
    "3.1.0": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"
    },
    "3.0.3": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"
    },
    "3.0.2": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.0.2/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.0.2/js/bootstrap.min.js"
    },
    "3.0.1": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.0.1/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.0.1/js/bootstrap.min.js"
    },
    "3.0.0": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"
    },
    "3.0.0-noicons": {
      "css": "//maxcdn.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.no-icons.min.css",
      "js": "//maxcdn.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"
    },
    "2.3.2": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"
    },
    "2.3.2-noicons": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.no-icons.min.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"
    },
    "2.3.1": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.1/js/bootstrap.min.js"
    },
    "2.3.0": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.0/css/bootstrap-combined.min.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.0/js/bootstrap.min.js"
    },
    "2.2.2": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.2.2/css/bootstrap-combined.min.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.2.2/js/bootstrap.min.js"
    },
    "2.2.2-noresponsible": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.2.2/css/bootstrap.min.nr.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.2.2/js/bootstrap.min.js"
    },

    "2.2.1": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"
    },
    "2.2.0": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.2.0/css/bootstrap-combined.min.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.2.0/js/bootstrap.min.js"
    },
    "2.1.1": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.1.1/css/bootstrap-combined.min.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.1.1/js/bootstrap.min.js"
    },
    "2.1.0": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.1.0/css/bootstrap-combined.min.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.1.0/js/bootstrap.min.js"
    },
    "2.0.4": {
      "css": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.0.4/css/bootstrap-combined.min.css",
      "js": "//maxcdn.bootstrapcdn.com/twitter-bootstrap/2.0.4/js/bootstrap.min.js"
    }
  },
  "fontawesome": {
    "4.4.0": "//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css",
    "4.2.0": "//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css",
    "4.1.0": "//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css",
    "4.0.3": "//maxcdn.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css",
    "4.0.2": "//maxcdn.bootstrapcdn.com/font-awesome/4.0.2/css/font-awesome.min.css",
    "4.0.1": "//maxcdn.bootstrapcdn.com/font-awesome/4.0.1/css/font-awesome.min.css",
    "4.0.0": "//maxcdn.bootstrapcdn.com/font-awesome/4.0.0/css/font-awesome.min.css",
    "3.2.1": "//maxcdn.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.min.css"
  }
}';
}

/**
 * Load CDN version optios.
 */
function _bootstrap_library_cdn_versions() {
  static $uri = 'http://netdna.bootstrapcdn.com/data/bootstrapcdn.json';
  $json_response = NULL;
  try {
    $client = \Drupal::httpClient();
    $response = $client->get($uri, array('headers' => array('Accept' => 'text/plain')));
	$data = (string) $response->getBody();
    if (empty($data)) {
      return FALSE;
    }
  }
  catch (RequestException $e) {
    watchdog_exception('bootstrap_library', $e->getMessage());
    return FALSE;
  }
  return $data;
}

?>