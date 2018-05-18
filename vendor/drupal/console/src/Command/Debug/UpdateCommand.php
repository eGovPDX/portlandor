<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\UpdateCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Update\UpdateRegistry;
use Drupal\Console\Utils\Site;

class UpdateCommand extends Command
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var UpdateRegistry
     */
    protected $postUpdateRegistry;

    /**
     * DebugCommand constructor.
     *
     * @param Site           $site
     * @param UpdateRegistry $postUpdateRegistry
     */
    public function __construct(
        Site $site,
        UpdateRegistry $postUpdateRegistry
    ) {
        $this->site = $site;
        $this->postUpdateRegistry = $postUpdateRegistry;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('debug:update')
            ->setDescription($this->trans('commands.debug.update.description'))
            ->setAliases(['du']);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->site->loadLegacyFile('/core/includes/update.inc');
        $this->site->loadLegacyFile('/core/includes/install.inc');

        drupal_load_updates();
        update_fix_compatibility();

        $requirements = update_check_requirements();
        $severity = drupal_requirements_severity($requirements);
        $updates = update_get_update_list();

        $this->getIo()->newLine();

        if ($severity == REQUIREMENT_ERROR || ($severity == REQUIREMENT_WARNING)) {
            $this->populateRequirements($requirements);
        } elseif (empty($updates)) {
            $this->getIo()->info($this->trans('commands.debug.update.messages.no-updates'));
        } else {
            $this->populateUpdate($updates);
            $this->populatePostUpdate();
        }
    }

    /**
     * @param $requirements
     */
    private function populateRequirements($requirements)
    {
        $this->getIo()->info($this->trans('commands.debug.update.messages.requirements-error'));

        $tableHeader = [
          $this->trans('commands.debug.update.messages.severity'),
          $this->trans('commands.debug.update.messages.title'),
          $this->trans('commands.debug.update.messages.value'),
          $this->trans('commands.debug.update.messages.description'),
        ];

        $tableRows = [];
        foreach ($requirements as $requirement) {
            $minimum = in_array(
                $requirement['minimum schema'],
                [REQUIREMENT_ERROR, REQUIREMENT_WARNING]
            );
            if ((isset($requirement['minimum schema'])) && ($minimum)) {
                $tableRows[] = [
                  $requirement['severity'],
                  $requirement['title'],
                  $requirement['value'],
                  $requirement['description'],
                ];
            }
        }

        $this->getIo()->table($tableHeader, $tableRows);
    }

    /**
     * @param $updates
     */
    private function populateUpdate($updates)
    {
        $this->getIo()->info($this->trans('commands.debug.update.messages.module-list'));
        $tableHeader = [
          $this->trans('commands.debug.update.messages.module'),
          $this->trans('commands.debug.update.messages.update-n'),
          $this->trans('commands.debug.update.messages.description')
        ];
        $tableRows = [];
        foreach ($updates as $module => $module_updates) {
            foreach ($module_updates['pending'] as $update_n => $update) {
                list(, $description) = explode($update_n . " - ", $update);
                $tableRows[] = [
                  $module,
                  $update_n,
                  trim($description),
                ];
            }
        }
        $this->getIo()->table($tableHeader, $tableRows);
    }

    private function populatePostUpdate()
    {
        $this->getIo()->info(
            $this->trans('commands.debug.update.messages.module-list-post-update')
        );
        $tableHeader = [
          $this->trans('commands.debug.update.messages.module'),
          $this->trans('commands.debug.update.messages.post-update'),
          $this->trans('commands.debug.update.messages.description')
        ];

        $postUpdates = $this->postUpdateRegistry->getPendingUpdateInformation();
        $tableRows = [];
        foreach ($postUpdates as $module => $module_updates) {
            foreach ($module_updates['pending'] as $postUpdateFunction => $message) {
                $tableRows[] = [
                  $module,
                  $postUpdateFunction,
                  $message,
                ];
            }
        }
        $this->getIo()->table($tableHeader, $tableRows);
    }
}
