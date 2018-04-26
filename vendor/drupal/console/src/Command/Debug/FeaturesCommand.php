<?php

/**
* @file
* Contains \Drupal\Console\Command\Debug\FeaturesCommand.
*/

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\FeatureTrait;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Command;

/**
 * @DrupalCommand(
 *     extension = "features",
 *     extensionType = "module"
 * )
 */

class FeaturesCommand extends Command
{
    use FeatureTrait;

    protected function configure()
    {
        $this
            ->setName('debug:features')
            ->setDescription($this->trans('commands.debug.features.description'))
            ->addArgument(
                'bundle',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.features.arguments.bundle')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundle= $input->getArgument('bundle');

        $tableHeader = [
            $this->trans('commands.debug.features.messages.bundle'),
            $this->trans('commands.debug.features.messages.name'),
            $this->trans('commands.debug.features.messages.machine-name'),
            $this->trans('commands.debug.features.messages.status'),
            $this->trans('commands.debug.features.messages.state'),
        ];

        $tableRows = [];

        $features = $this->getFeatureList($bundle);

        foreach ($features as $feature) {
            $tableRows[] = [$feature['bundle_name'],$feature['name'], $feature['machine_name'], $feature['status'],$feature['state']];
        }

        $this->getIo()->table($tableHeader, $tableRows, 'compact');
    }
}
