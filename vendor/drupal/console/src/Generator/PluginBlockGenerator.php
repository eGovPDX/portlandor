<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\PluginBlockGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class PluginBlockGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PermissionGenerator constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $inputs = $parameters['inputs'];
        $module = $parameters['module'];
        $class_name = $parameters['class_name'];

        // Consider the type when determining a default value. Figure out what
        // the code looks like for the default value tht we need to generate.
        foreach ($inputs as &$input) {
            $default_code = '$this->t(\'\')';
            if ($input['default_value'] == '') {
                switch ($input['type']) {
                case 'checkbox':
                case 'number':
                case 'weight':
                case 'radio':
                    $default_code = 0;
                    break;

                case 'radios':
                case 'checkboxes':
                    $default_code = 'array()';
                    break;
                }
            } elseif (substr($input['default_value'], 0, 1) == '$') {
                // If they want to put in code, let them, they're programmers.
                $default_code = $input['default_value'];
            } elseif (is_numeric($input['default_value'])) {
                $default_code = $input['default_value'];
            } elseif (preg_match('/^(true|false)$/i', $input['default_value'])) {
                // Coding Standards
                $default_code = strtoupper($input['default_value']);
            } else {
                $default_code = '$this->t(\'' . $input['default_value'] . '\')';
            }
            $input['default_code'] = $default_code;
        }

        $this->renderFile(
            'module/src/Plugin/Block/block.php.twig',
            $this->extensionManager->getPluginPath($module, 'Block') . '/' . $class_name . '.php',
            $parameters
        );
    }
}
