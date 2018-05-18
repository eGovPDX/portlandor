<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginFieldWidgetCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginFieldWidgetGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Core\Field\FieldTypePluginManager;

/**
 * Class PluginFieldWidgetCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginFieldWidgetCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var PluginFieldWidgetGenerator
     */
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var FieldTypePluginManager
     */
    protected $fieldTypePluginManager;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * PluginFieldWidgetCommand constructor.
     *
     * @param Manager                    $extensionManager
     * @param PluginFieldWidgetGenerator $generator
     * @param StringConverter            $stringConverter
     * @param Validator                  $validator
     * @param FieldTypePluginManager     $fieldTypePluginManager
     * @param ChainQueue                 $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginFieldWidgetGenerator $generator,
        StringConverter $stringConverter,
        Validator $validator,
        FieldTypePluginManager $fieldTypePluginManager,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->fieldTypePluginManager = $fieldTypePluginManager;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:fieldwidget')
            ->setDescription($this->trans('commands.generate.plugin.fieldwidget.description'))
            ->setHelp($this->trans('commands.generate.plugin.fieldwidget.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.fieldwidget.options.class')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldwidget.options.label')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldwidget.options.plugin-id')
            )
            ->addOption(
                'field-type',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldwidget.options.field-type')
            )
            ->setAliases(['gpfw']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        $module = $input->getOption('module');
        $class_name = $this->validator->validateClassName($input->getOption('class'));
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $field_type = $input->getOption('field-type');

        $this->generator->generate([
            'module' => $module,
            'class_name' => $class_name,
            'label' => $label,
            'plugin_id' => $plugin_id,
            'field_type' => $field_type,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.fieldwidget.questions.class'),
                'ExampleFieldWidget',
                function ($class_name) {
                    return $this->validator->validateClassName($class_name);
                }
            );
            $input->setOption('class', $class_name);
        }

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.fieldwidget.questions.label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.fieldwidget.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --field-type option
        $field_type = $input->getOption('field-type');
        if (!$field_type) {
            // Gather valid field types.
            $field_type_options = [];
            foreach ($this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions()) as $category => $field_types) {
                foreach ($field_types as $name => $field_type) {
                    $field_type_options[] = $name;
                }
            }

            $field_type  = $this->getIo()->choice(
                $this->trans('commands.generate.plugin.fieldwidget.questions.field-type'),
                $field_type_options
            );

            $input->setOption('field-type', $field_type);
        }
    }
}
