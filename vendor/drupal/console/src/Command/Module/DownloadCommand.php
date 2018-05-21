<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\DownloadCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Utils\DrupalApi;
use GuzzleHttp\Client;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Utils\Site;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\ShellProcess;

class DownloadCommand extends Command
{
    use ProjectDownloadTrait;

    /**
 * @var DrupalApi
*/
    protected $drupalApi;

    /**
 * @var Client
*/
    protected $httpClient;

    /**
     * @var string
     */
    protected $appRoot;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var Validator
*/
    protected $validator;

    /**
 * @var ConfigurationManager
*/
    protected $configurationManager;

    /**
 * @var ShellProcess
*/
    protected $shellProcess;

    /**
     * @var string
     */
    protected $root;

    /**
     * DownloadCommand constructor.
     *
     * @param DrupalApi            $drupalApi
     * @param Client               $httpClient
     * @param $appRoot
     * @param Manager              $extensionManager
     * @param Validator            $validator
     * @param Site                 $site
     * @param ConfigurationManager $configurationManager
     * @param ShellProcess         $shellProcess
     * @param $root
     */
    public function __construct(
        DrupalApi $drupalApi,
        Client $httpClient,
        $appRoot,
        Manager $extensionManager,
        Validator $validator,
        Site $site,
        ConfigurationManager $configurationManager,
        ShellProcess $shellProcess,
        $root
    ) {
        $this->drupalApi = $drupalApi;
        $this->httpClient = $httpClient;
        $this->appRoot = $appRoot;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->site = $site;
        $this->configurationManager = $configurationManager;
        $this->shellProcess = $shellProcess;
        $this->root = $root;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('module:download')
            ->setDescription($this->trans('commands.module.download.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.download.arguments.module')
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.module.download.options.path')
            )
            ->addOption(
                'latest',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.download.options.latest')
            )
            ->addOption(
                'composer',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.install.options.composer')
            )
            ->addOption(
                'unstable',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.download.options.unstable')
            )
            ->setAliases(['mod']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $composer = $input->getOption('composer');
        $module = $input->getArgument('module');

        if (!$module) {
            $module = $this->modulesQuestion();
            $input->setArgument('module', $module);
        }

        if (!$composer) {
            $path = $input->getOption('path');
            if (!$path) {
                $path = $this->getIo()->ask(
                    $this->trans('commands.module.download.questions.path'),
                    'modules/contrib'
                );
                $input->setOption('path', $path);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $input->getArgument('module');
        $latest = $input->getOption('latest');
        $path = $input->getOption('path');
        $composer = $input->getOption('composer');
        $unstable = true;

        if ($composer) {
            foreach ($modules as $module) {
                if (!$latest) {
                    $versions = $this->drupalApi
                        ->getPackagistModuleReleases($module, 10, $unstable);

                    if (!$versions) {
                        $this->getIo()->error(
                            sprintf(
                                $this->trans(
                                    'commands.module.download.messages.no-releases'
                                ),
                                $module
                            )
                        );

                        return 1;
                    } else {
                        $version = $this->getIo()->choice(
                            sprintf(
                                $this->trans(
                                    'commands.site.new.questions.composer-release'
                                ),
                                $module
                            ),
                            $versions
                        );
                    }
                } else {
                    $versions = $this->drupalApi
                        ->getPackagistModuleReleases($module, 10, $unstable);

                    if (!$versions) {
                        $this->getIo()->error(
                            sprintf(
                                $this->trans(
                                    'commands.module.download.messages.no-releases'
                                ),
                                $module
                            )
                        );
                        return 1;
                    } else {
                        $version = current(
                            $this->drupalApi
                                ->getPackagistModuleReleases($module, 1, $unstable)
                        );
                    }
                }

                // Register composer repository
                $command = "composer config repositories.drupal composer https://packages.drupal.org/8";
                $this->shellProcess->exec($command, $this->root);

                $command = sprintf(
                    'composer require drupal/%s:%s --prefer-dist --optimize-autoloader --sort-packages --update-no-dev',
                    $module,
                    $version
                );

                if ($this->shellProcess->exec($command, $this->root)) {
                    $this->getIo()->success(
                        sprintf(
                            $this->trans('commands.module.download.messages.composer'),
                            $module
                        )
                    );
                }
            }
        } else {
            $this->downloadModules($modules, $latest, $path);
        }

        return true;
    }
}
