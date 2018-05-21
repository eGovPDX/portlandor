<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginViewsFieldCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginViewsFieldGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Utils\Site;
use Drupal\Console\Core\Utils\StringConverter;

/**
 * Class PluginViewsFieldCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginViewsFieldCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var PluginViewsFieldGenerator
     */
    protected $generator;

    /**
     * @var Site
     */
    protected $site;

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
     * PluginViewsFieldCommand constructor.
     *
     * @param Manager                   $extensionManager
     * @param PluginViewsFieldGenerator $generator
     * @param Site                      $site
     * @param StringConverter           $stringConverter
     * @param Validator                 $validator
     * @param ChainQueue                $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginViewsFieldGenerator $generator,
        Site $site,
        StringConverter $stringConverter,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->site = $site;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:views:field')
            ->setDescription($this->trans('commands.generate.plugin.views.field.description'))
            ->setHelp($this->trans('commands.generate.plugin.views.field.help'))
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
                $this->trans('commands.generate.plugin.views.field.options.class')
            )
            ->addOption(
                'title',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.views.field.options.title')
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.views.field.options.description')
            )
            ->setAliases(['gpvf']);
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
        $class_machine_name = $this->stringConverter->camelCaseToUnderscore($class_name);
        $title = $input->getOption('title');
        $description = $input->getOption('description');

        $this->generator->generate([
            'module' => $module,
            'class_machine_name' => $class_machine_name,
            'class_name' => $class_name,
            'title' => $title,
            'description' => $description,
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
                $this->trans('commands.generate.plugin.views.field.questions.class'),
                'CustomViewsField',
                function ($class_name) {
                    return $this->validator->validateClassName($class_name);
                }
            );
        }
        $input->setOption('class', $class_name);

        // --title option
        $title = $input->getOption('title');
        if (!$title) {
            $title = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.views.field.questions.title'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('title', $title);
        }

        // --description option
        $description = $input->getOption('description');
        if (!$description) {
            $description = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.views.field.questions.description'),
                $this->trans('commands.generate.plugin.views.field.questions.description_default')
            );
            $input->setOption('description', $description);
        }
    }
}
