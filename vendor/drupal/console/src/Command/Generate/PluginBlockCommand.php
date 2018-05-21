<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginBlockCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ArrayInputTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Generator\PluginBlockGenerator;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;

class PluginBlockCommand extends ContainerAwareCommand
{
    use ArrayInputTrait;
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var PluginBlockGenerator
     */
    protected $generator;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var ElementInfoManagerInterface
     */
    protected $elementInfoManager;

    /**
     * PluginBlockCommand constructor.
     *
     * @param ConfigFactory               $configFactory
     * @param ChainQueue                  $chainQueue
     * @param PluginBlockGenerator        $generator
     * @param EntityTypeManagerInterface  $entityTypeManager
     * @param Manager                     $extensionManager
     * @param Validator                   $validator
     * @param StringConverter             $stringConverter
     * @param ElementInfoManagerInterface $elementInfoManager
     */
    public function __construct(
        ConfigFactory $configFactory,
        ChainQueue $chainQueue,
        PluginBlockGenerator $generator,
        EntityTypeManagerInterface $entityTypeManager,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter,
        ElementInfoManagerInterface $elementInfoManager
    ) {
        $this->configFactory = $configFactory;
        $this->chainQueue = $chainQueue;
        $this->generator = $generator;
        $this->entityTypeManager = $entityTypeManager;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        $this->elementInfoManager = $elementInfoManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:block')
            ->setDescription($this->trans('commands.generate.plugin.block.description'))
            ->setHelp($this->trans('commands.generate.plugin.block.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.class')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.label')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.plugin-id')
            )
            ->addOption(
                'theme-region',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.block.options.theme-region')
            )
            ->addOption(
                'inputs',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.inputs')
            )
            ->addOption(
                'services',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )
            ->setAliases(['gpb']);
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
        $services = $input->getOption('services');
        $theme_region = $input->getOption('theme-region');
        $inputs = $input->getOption('inputs');
        $noInteraction = $input->getOption('no-interaction');
        // Parse nested data.
        if ($noInteraction) {
            $inputs = $this->explodeInlineArray($inputs);
        }

        $theme = $this->configFactory->get('system.theme')->get('default');
        $themeRegions = \system_region_list($theme, REGIONS_VISIBLE);

        if (!empty($theme_region) && !isset($themeRegions[$theme_region])) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.generate.plugin.block.messages.invalid-theme-region'),
                    $theme_region
                )
            );

            return 1;
        }

        // @see use Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        $this->generator->generate([
          'module' => $module,
          'class_name' => $class_name,
          'label' => $label,
          'plugin_id' => $plugin_id,
          'services' => $build_services,
          'inputs' => $inputs,
        ]);


        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        if ($theme_region) {
            $block = $this->entityTypeManager
                ->getStorage('block')
                ->create([
                    'id'=> $plugin_id,
                    'plugin' => $plugin_id,
                    'theme' => $theme,
                ]);
            $block->setRegion($theme_region);
            $block->save();
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $theme = $this->configFactory->get('system.theme')->get('default');
        $themeRegions = \system_region_list($theme, REGIONS_VISIBLE);

        // --module option
        $this->getModuleOption();

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.block.questions.class'),
                'DefaultBlock',
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.block.questions.label'),
                $this->stringConverter->camelCaseToHuman($class)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $pluginId = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.block.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $pluginId);
        }

        // --theme-region option
        $themeRegion = $input->getOption('theme-region');
        if (!$themeRegion) {
            $themeRegion = $this->getIo()->choiceNoList(
                $this->trans('commands.generate.plugin.block.questions.theme-region'),
                array_values($themeRegions),
                '',
                true
            );
            $themeRegion = array_search($themeRegion, $themeRegions);
            $input->setOption('theme-region', $themeRegion);
        }

        // --services option
        // @see Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
        $services = $this->servicesQuestion();
        $input->setOption('services', $services);

        $output->writeln($this->trans('commands.generate.plugin.block.messages.inputs'));

        // --inputs option
        $inputs = $input->getOption('inputs');
        if (!$inputs) {
            // @see \Drupal\Console\Command\Shared\FormTrait::formQuestion
            $inputs = $this->formQuestion();
            $input->setOption('inputs', $inputs);
        } else {
            $inputs = $this->explodeInlineArray($inputs);
        }
        $input->setOption('inputs', $inputs);
    }
}
