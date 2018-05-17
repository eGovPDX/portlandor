<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Migrate\ExecuteCommand.
 */

namespace Drupal\Console\Command\Migrate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutable;
use Drupal\Console\Utils\MigrateExecuteMessageCapture;
use Drupal\Console\Command\Shared\MigrationTrait;
use Drupal\Console\Command\Shared\DatabaseTrait;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\State\StateInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * @DrupalCommand(
 *     extension = "migrate",
 *     extensionType = "module"
 * )
 */
class ExecuteCommand extends Command
{
    use DatabaseTrait;
    use MigrationTrait;

    protected $migrateConnection;

    /**
     * @var MigrationPluginManagerInterface $pluginManagerMigration
     */
    protected $pluginManagerMigration;

    /**
     * DebugCommand constructor.
     *
     * @param MigrationPluginManagerInterface $pluginManagerMigration
     */
    public function __construct(
        MigrationPluginManagerInterface $pluginManagerMigration
    ) {
        $this->pluginManagerMigration = $pluginManagerMigration;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('migrate:execute')
            ->setDescription($this->trans('commands.migrate.execute.description'))
            ->addArgument('migration-ids', InputArgument::IS_ARRAY, $this->trans('commands.migrate.execute.arguments.id'))
            ->addOption(
                'site-url',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.site-url')
            )
            ->addOption(
                'db-type',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.migrations.options.db-type')
            )
            ->addOption(
                'db-host',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-host')
            )
            ->addOption(
                'db-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-name')
            )
            ->addOption(
                'db-user',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-user')
            )
            ->addOption(
                'db-pass',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.execute.options.db-pass')
            )
            ->addOption(
                'db-prefix',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.execute.options.db-prefix')
            )
            ->addOption(
                'db-port',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.migrate.execute.options.db-port')
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.migrate.execute.options.exclude'),
                []
            )
            ->addOption(
                'source-base_path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.migrate.execute.options.source-base-path')
            )
            ->setAliases(['mie']);
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $validator_required = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception('The option can not be empty');
            }

            return $value;
        };

        // --site-url option
        $site_url = $input->getOption('site-url');
        if (!$site_url) {
            $site_url = $this->getIo()->ask(
                $this->trans('commands.migrate.execute.questions.site-url'),
                'http://www.example.com',
                $validator_required
            );
            $input->setOption('site-url', $site_url);
        }

        // --db-type option
        $db_type = $input->getOption('db-type');
        if (!$db_type) {
            $db_type = $this->dbDriverTypeQuestion();
            $input->setOption('db-type', $db_type);
        }
        
        // --db-host option
        $db_host = $input->getOption('db-host');
        if (!$db_host) {
            $db_host = $this->dbHostQuestion();
            $input->setOption('db-host', $db_host);
        }

        // --db-name option
        $db_name = $input->getOption('db-name');
        if (!$db_name) {
            $db_name = $this->dbNameQuestion();
            $input->setOption('db-name', $db_name);
        }

        // --db-user option
        $db_user = $input->getOption('db-user');
        if (!$db_user) {
            $db_user = $this->dbUserQuestion();
            $input->setOption('db-user', $db_user);
        }

        // --db-pass option
        $db_pass = $input->getOption('db-pass');
        if (!$db_pass) {
            $db_pass = $this->dbPassQuestion();
            $input->setOption('db-pass', $db_pass);
        }

        // --db-prefix
        $db_prefix = $input->getOption('db-prefix');
        if (!$db_prefix) {
            $db_prefix = $this->dbPrefixQuestion();
            $input->setOption('db-prefix', $db_prefix);
        }

        // --db-port prefix
        $db_port = $input->getOption('db-port');
        if (!$db_port) {
            $db_port = $this->dbPortQuestion();
            $input->setOption('db-port', $db_port);
        }
        
        $this->registerMigrateDB();
        $this->migrateConnection = $this->getDBConnection('default', 'upgrade');

        if (!$drupal_version = $this->getLegacyDrupalVersion($this->migrateConnection)) {
            $this->getIo()->error($this->trans('commands.migrate.setup.migrations.questions.not-drupal'));
            return 1;
        }
        
        $database = $this->getDBInfo();
        $version_tag = 'Drupal ' . $drupal_version;
         
        // Get migrations
        $migrations_list = $this->getMigrations($version_tag);

        // --migration-id prefix
        $migration_id = $input->getArgument('migration-ids');

        if (!in_array('all', $migration_id)) {
            $migrations = $migrations_list;
        } else {
            $migrations = array_keys($this->getMigrations($version_tag));
        }
         
        if (!$migration_id) {
            $migrations_ids = [];
 
            while (true) {
                $migration_id = $this->getIo()->choiceNoList(
                    $this->trans('commands.migrate.execute.questions.id'),
                    array_keys($migrations_list),
                    'all'
                );

                if (empty($migration_id) || $migration_id == 'all') {
                    // Only add all if it's the first option
                    if (empty($migrations_ids) && $migration_id == 'all') {
                        $migrations_ids[] = $migration_id;
                    }
                    break;
                } else {
                    $migrations_ids[] = $migration_id;
                }
            }

            $input->setArgument('migration-ids', $migrations_ids);
        }
        
        // --migration-id prefix
        $exclude_ids = $input->getOption('exclude');
        if (!$exclude_ids) {
            unset($migrations_list['all']);
            while (true) {
                $exclude_id = $this->getIo()->choiceNoList(
                    $this->trans('commands.migrate.execute.questions.exclude-id'),
                    array_keys($migrations_list),
                    '',
                    true
                );

                if (empty($exclude_id) || is_numeric($exclude_id)) {
                    break;
                } else {
                    unset($migrations_list[$exclude_id]);
                    $exclude_ids[] = $exclude_id;
                }
            }
            $input->setOption('exclude', $exclude_ids);
        }

        // --source-base_path
        $sourceBasepath = $input->getOption('source-base_path');
        if (!$sourceBasepath) {
            $sourceBasepath = $this->getIo()->ask(
                $this->trans('commands.migrate.setup.questions.source-base-path'),
                ''
            );
            $input->setOption('source-base_path', $sourceBasepath);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migration_ids = $input->getArgument('migration-ids');
        $exclude_ids = $input->getOption('exclude');

        $sourceBasepath = $input->getOption('source-base_path');
        $configuration['source']['constants']['source_base_path'] = rtrim($sourceBasepath, '/') . '/';


        // If migrations weren't provided finish execution
        if (empty($migration_ids)) {
            return 1;
        }

        if (!$this->migrateConnection) {
            $this->registerMigrateDB();
            $this->migrateConnection = $this->getDBConnection('default', 'upgrade');
        }
        
        if (!$drupal_version = $this->getLegacyDrupalVersion($this->migrateConnection)) {
            $this->getIo()->error($this->trans('commands.migrate.setup.migrations.questions.not-drupal'));
            return 1;
        }
        
        $version_tag = 'Drupal ' . $drupal_version;
        
        if (!in_array('all', $migration_ids)) {
            $migrations = $migration_ids;
        } else {
            $migrations = array_keys($this->getMigrations($version_tag));
        }
                
        if (!empty($exclude_ids)) {
            // Remove exclude migration from migration script
            $migrations = array_diff($migrations, $exclude_ids);
        }
        
        if (count($migrations) == 0) {
            $this->getIo()->error($this->trans('commands.migrate.execute.messages.no-migrations'));
            return 1;
        }

        foreach ($migrations as $migration_id) {
            $this->getIo()->info(
                sprintf(
                    $this->trans('commands.migrate.execute.messages.processing'),
                    $migration_id
                )
            );

            $migration_service = $this->pluginManagerMigration->createInstance($migration_id, $configuration);

            if ($migration_service) {
                $messages = new MigrateExecuteMessageCapture();
                $executable = new MigrateExecutable($migration_service, $messages);
                $migration_status = $executable->import();
                switch ($migration_status) {
                case MigrationInterface::RESULT_COMPLETED:
                    $this->getIo()->info(
                        sprintf(
                            $this->trans('commands.migrate.execute.messages.imported'),
                            $migration_id
                        )
                    );
                    break;
                case MigrationInterface::RESULT_INCOMPLETE:
                    $this->getIo()->info(
                        sprintf(
                            $this->trans('commands.migrate.execute.messages.importing-incomplete'),
                            $migration_id
                        )
                    );
                    break;
                case MigrationInterface::RESULT_STOPPED:
                    $this->getIo()->error(
                        sprintf(
                            $this->trans('commands.migrate.execute.messages.import-stopped'),
                            $migration_id
                        )
                    );
                    break;
                case MigrationInterface::RESULT_FAILED:
                    $this->getIo()->error(
                        sprintf(
                            $this->trans('commands.migrate.execute.messages.import-fail'),
                            $migration_id
                        )
                    );
                    break;
                case MigrationInterface::RESULT_SKIPPED:
                    $this->getIo()->error(
                        sprintf(
                            $this->trans('commands.migrate.execute.messages.import-skipped'),
                            $migration_id
                        )
                    );
                    break;
                case MigrationInterface::RESULT_DISABLED:
                    // Skip silently if disabled.
                    break;
                }
            } else {
                $this->getIo()->error($this->trans('commands.migrate.execute.messages.fail-load'));

                return 1;
            }
        }

        return 0;
    }
}
