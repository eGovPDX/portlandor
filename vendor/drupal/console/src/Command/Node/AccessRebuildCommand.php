<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Node\AccessRebuildCommand.
 */

namespace Drupal\Console\Command\Node;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\State\StateInterface;

/**
 * Class AccessRebuildCommand
 *
 * @package Drupal\Console\Command\Node
 */
class AccessRebuildCommand extends Command
{
    /**
     * @var StateInterface
     */
    protected $state;

    /**
     * AccessRebuildCommand constructor.
     *
     * @param StateInterface $state
     */
    public function __construct(StateInterface $state)
    {
        $this->state = $state;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('node:access:rebuild')
            ->setDescription($this->trans('commands.node.access.rebuild.description'))
            ->addOption(
                'batch',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.node.access.rebuild.options.batch')
            )->setAliases(['nar']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getIo()->newLine();
        $this->getIo()->comment(
            $this->trans('commands.node.access.rebuild.messages.rebuild')
        );

        $batch = $input->getOption('batch');
        try {
            node_access_rebuild($batch);
        } catch (\Exception $e) {
            $this->getIo()->error($e->getMessage());

            return 1;
        }

        $needs_rebuild = $this->state->get('node.node_access_needs_rebuild') ? : false;
        if ($needs_rebuild) {
            $this->getIo()->error(
                $this->trans('commands.node.access.rebuild.messages.failed')
            );

            return 1;
        }

        $this->getIo()->success(
            $this->trans('commands.node.access.rebuild.messages.completed')
        );
        return 0;
    }
}
