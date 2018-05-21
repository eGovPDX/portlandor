<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Create\TermsCommand.
 */

namespace Drupal\Console\Command\Create;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Utils\Create\TermData;
use Drupal\Console\Utils\DrupalApi;

/**
 * Class TermsCommand
 *
 * @package Drupal\Console\Command\Generate
 *
 * @DrupalCommand(
 *     extension = "taxonomy",
 *     extensionType = "module"
 * )
 */
class TermsCommand extends Command
{
    /**
     * @var DrupalApi
     */
    protected $drupalApi;
    /**
     * @var TermData
     */
    protected $createTermData;

    /**
     * TermsCommand constructor.
     *
     * @param DrupalApi $drupalApi
     * @param TermData  $createTermData
     */
    public function __construct(
        DrupalApi $drupalApi,
        TermData $createTermData
    ) {
        $this->drupalApi = $drupalApi;
        $this->createTermData = $createTermData;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:terms')
            ->setDescription($this->trans('commands.create.terms.description'))
            ->addArgument(
                'vocabularies',
                InputArgument::IS_ARRAY,
                $this->trans('commands.create.terms.arguments.vocabularies')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.terms.options.limit')
            )
            ->addOption(
                'name-words',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.terms.options.name-words')
            )->setAliases(['crt']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $vocabularies = $input->getArgument('vocabularies');
        if (!$vocabularies) {
            $vocabularies = $this->drupalApi->getVocabularies();
            $vids = $this->getIo()->choice(
                $this->trans('commands.create.terms.questions.vocabularies'),
                array_values($vocabularies),
                null,
                true
            );

            $vids = array_map(
                function ($vid) use ($vocabularies) {
                    return array_search($vid, $vocabularies);
                },
                $vids
            );

            $input->setArgument('vocabularies', $vids);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $this->getIo()->ask(
                $this->trans('commands.create.terms.questions.limit'),
                25
            );
            $input->setOption('limit', $limit);
        }

        $nameWords = $input->getOption('name-words');
        if (!$nameWords) {
            $nameWords = $this->getIo()->ask(
                $this->trans('commands.create.terms.questions.name-words'),
                5
            );

            $input->setOption('name-words', $nameWords);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $vocabularies = $input->getArgument('vocabularies');
        $limit = $input->getOption('limit')?:25;
        $nameWords = $input->getOption('name-words')?:5;

        if (!$vocabularies) {
            $vocabularies = array_keys($this->drupalApi->getVocabularies());
        }

        $result = $this->createTermData->create(
            $vocabularies,
            $limit,
            $nameWords
        );

        $tableHeader = [
            $this->trans('commands.create.terms.messages.term-id'),
            $this->trans('commands.create.terms.messages.vocabulary'),
            $this->trans('commands.create.terms.messages.name'),
        ];

        if ($result['success']) {
            $this->getIo()->table($tableHeader, $result['success']);

            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.create.terms.messages.created-terms'),
                    count($result['success'])
                )
            );
        }

        if (isset($result['error'])) {
            foreach ($result['error'] as $error) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.create.terms.messages.error'),
                        $error
                    )
                );
            }
        }

        return 0;
    }
}
