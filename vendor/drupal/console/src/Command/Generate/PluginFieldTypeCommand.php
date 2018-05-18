<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginFieldTypeCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginFieldTypeGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Core\Field\FieldTypePluginManager;

/**
 * Class PluginFieldTypeCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginFieldTypeCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var PluginFieldTypeGenerator
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
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * PluginFieldTypeCommand constructor.
     *
     * @param Manager                  $extensionManager
     * @param PluginFieldTypeGenerator $generator
     * @param StringConverter          $stringConverter
     * @param Validator                $validator
     * @param ChainQueue               $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginFieldTypeGenerator $generator,
        StringConverter $stringConverter,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:fieldtype')
            ->setDescription($this->trans('commands.generate.plugin.fieldtype.description'))
            ->setHelp($this->trans('commands.generate.plugin.fieldtype.help'))
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
                $this->trans('commands.generate.plugin.fieldtype.options.class')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.label')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.plugin-id')
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.description')
            )
            ->addOption(
                'default-widget',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.default-widget')
            )
            ->addOption(
                'default-formatter',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.default-formatter')
            )
            ->setAliases(['gpft']);
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
        $description = $input->getOption('description');
        $default_widget = $input->getOption('default-widget');
        $default_formatter = $input->getOption('default-formatter');

        $this->generator->generate([
            'module' => $module,
            'class_name' => $class_name,
            'label' => $label,
            'plugin_id' => $plugin_id,
            'description' => $description,
            'default_widget' => $default_widget,
            'default_formatter' => $default_formatter,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery'], false);

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
                $this->trans('commands.generate.plugin.fieldtype.questions.class'),
                'ExampleFieldType',
                function ($class_name) {
                    return $this->validator->validateClassName($class_name);
                }
            );
            $input->setOption('class', $class_name);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.fieldtype.questions.label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.fieldtype.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --description option
        $description = $input->getOption('description');
        if (!$description) {
            $description = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.fieldtype.questions.description'),
                $this->trans('commands.generate.plugin.fieldtype.suggestions.my-field-type')
            );
            $input->setOption('description', $description);
        }

        // --default-widget option
        $default_widget = $input->getOption('default-widget');
        if (!$default_widget) {
            $default_widget = $this->getIo()->askEmpty(
                $this->trans('commands.generate.plugin.fieldtype.questions.default-widget')
            );
            $input->setOption('default-widget', $default_widget);
        }

        // --default-formatter option
        $default_formatter = $input->getOption('default-formatter');
        if (!$default_formatter) {
            $default_formatter = $this->getIo()->askEmpty(
                $this->trans('commands.generate.plugin.fieldtype.questions.default-formatter')
            );
            $input->setOption('default-formatter', $default_formatter);
        }
    }
}
