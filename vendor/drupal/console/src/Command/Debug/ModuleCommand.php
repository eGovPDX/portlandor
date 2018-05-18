<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\ModuleCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Utils\Site;
use GuzzleHttp\Client;
use Drupal\Console\Core\Utils\ConfigurationManager;

class ModuleCommand extends Command
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var Site
     */
    protected $site;

    /**
     * DebugCommand constructor.
     *
     * @param Client  $httpClient
     */

    protected $httpClient;

    /**
     * ChainDebugCommand constructor.
     *
     * @param ConfigurationManager $configurationManager
     * @param Site                 $site
     */
    public function __construct(
        ConfigurationManager $configurationManager,
        Site $site,
        Client $httpClient
    ) {
        $this->configurationManager = $configurationManager;
        $this->site = $site;
        $this->httpClient = $httpClient;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('debug:module')
            ->setDescription($this->trans('commands.debug.module.description'))
            ->addArgument(
                'module',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                $this->trans('commands.debug.module.module')
            )
            ->addOption(
                'status',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.module.options.status')
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.module.options.type')
            )
            ->setAliases(['dm']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->site->loadLegacyFile('/core/modules/system/system.module');

        $status = strtolower($input->getOption('status'));
        $type = strtolower($input->getOption('type'));
        $modules = strtolower($input->getArgument('module'));

        if ($modules) {
            $config = $this->configurationManager->getConfiguration();
            $repo = $config->get('application.composer.repositories.default');

            foreach ($modules as $module) {
                $url = sprintf(
                    '%s/packages/drupal/%s.json',
                    $config->get('application.composer.packages.default'),
                    $module
                );

                try {
                    $data = $this->httpClient->getUrlAsJson($repo . $url);
                } catch (\Exception $e) {
                    $this->getIo()->error(
                        sprintf(
                            $this->trans('commands.debug.module.messages.no-results'),
                            $module
                        )
                    );

                    return 1;
                }

                $tableHeader = [
                  '<info>'.$data->package->name.'</info>'
                ];

                $tableRows = [];

                $tableRows[] = [
                  $data->package->description
                ];

                $tableRows[] = [
                  '<comment>'.$this->trans('commands.debug.module.messages.total-downloads').'</comment>',
                  $data->package->downloads->total
                ];

                $tableRows[] = [
                  '<comment>'.$this->trans('commands.debug.module.messages.total-monthly').'</comment>',
                  $data->package->downloads->monthly
                ];

                $tableRows[] = [
                  '<comment>'.$this->trans('commands.debug.module.messages.total-daily').'</comment>',
                  $data->package->downloads->daily
                ];

                $this->getIo()->table($tableHeader, $tableRows, 'compact');
            }
            return 0;
        }

        if ($status == 'installed') {
            $status = 1;
        } elseif ($status == 'uninstalled') {
            $status = 0;
        } else {
            $status = -1;
        }

        if ($type == 'core') {
            $type = 'core';
        } elseif ($type == 'no-core') {
            $type = '';
        } else {
            $type = null;
        }

        $tableHeader = [
          $this->trans('commands.debug.module.messages.id'),
          $this->trans('commands.debug.module.messages.name'),
          $this->trans('commands.debug.module.messages.package'),
          $this->trans('commands.debug.module.messages.version'),
          $this->trans('commands.debug.module.messages.schema-version'),
          $this->trans('commands.debug.module.messages.status'),
          $this->trans('commands.debug.module.messages.origin'),
        ];

        $tableRows = [];
        $modules = system_rebuild_module_data();
        foreach ($modules as $module_id => $module) {
            if ($status >= 0 && $status != $module->status) {
                continue;
            }

            if ($type !== null && $type !== $module->origin) {
                continue;
            }

            $module_status = ($module->status) ? $this->trans('commands.debug.module.messages.installed') : $this->trans('commands.debug.module.messages.uninstalled');
            $module_origin = ($module->origin) ? $module->origin : 'no core';
            $schema_version = (drupal_get_installed_schema_version($module_id)!= -1?drupal_get_installed_schema_version($module_id): '');

            $tableRows [] = [
              $module_id,
              $module->info['name'],
              $module->info['package'],
              $module->info['version'],
              $schema_version,
              $module_status,
              $module_origin,
            ];
        }

        $this->getIo()->table($tableHeader, $tableRows, 'compact');
    }
}
