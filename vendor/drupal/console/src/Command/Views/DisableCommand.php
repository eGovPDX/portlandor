<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Views\DisableCommand.
 */

namespace Drupal\Console\Command\Views;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Class DisableCommand
 *
 * @package Drupal\Console\Command\Views
 */
class DisableCommand extends Command
{
    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var QueryFactory
     */
    protected $entityQuery;

    /**
     * DisableCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param QueryFactory               $entityQuery
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        QueryFactory $entityQuery
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityQuery = $entityQuery;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('views:disable')
            ->setDescription($this->trans('commands.views.disable.description'))
            ->addArgument(
                'view-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.views.arguments.view-id')
            )
            ->setAliases(['vd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $viewId = $input->getArgument('view-id');
        if (!$viewId) {
            $views = $this->entityQuery
                ->get('view')
                ->condition('status', 1)
                ->execute();
            $viewId = $this->getIo()->choiceNoList(
                $this->trans('commands.debug.views.arguments.view-id'),
                $views
            );
            $input->setArgument('view-id', $viewId);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $viewId = $input->getArgument('view-id');

        $view = $this->entityTypeManager->getStorage('view')->load($viewId);

        if (empty($view)) {
            $this->getIo()->error(sprintf($this->trans('commands.debug.views.messages.not-found'), $viewId));

            return 1;
        }

        try {
            $view->disable()->save();

            $this->getIo()->success(sprintf($this->trans('commands.views.disable.messages.disabled-successfully'), $view->get('label')));
        } catch (\Exception $e) {
            $this->getIo()->error($e->getMessage());

            return 1;
        }

        return 0;
    }
}
