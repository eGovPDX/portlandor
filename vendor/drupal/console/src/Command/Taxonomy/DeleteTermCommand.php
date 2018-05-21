<?php

namespace Drupal\Console\Command\Taxonomy;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class DeleteTermCommand.
 *
 * @package Drupal\eco_migrate
 */
class DeleteTermCommand extends Command
{
    /**
     * The entity_type storage.
     *
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * InfoCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('taxonomy:term:delete')
            ->setDescription($this->trans('commands.taxonomy.term.delete.description'))
            ->addArgument(
                'vid',
                InputArgument::REQUIRED
            )->setAliases(['ttd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $vid = $input->getArgument('vid');

        if ($vid === 'all') {
            $vid = $vid;
        } elseif (!in_array($vid, array_keys($this->getVocabularies()))) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.taxonomy.term.delete.messages.invalid-vocabulary'),
                    $vid
                )
            );
            return;
        }
        $this->deleteExistingTerms($vid);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --vid argument
        $vid = $input->getArgument('vid');
        if (!$vid) {
            $vid = $this->getIo()->choiceNoList(
                $this->trans('commands.taxonomy.term.delete.vid'),
                array_keys($this->getVocabularies())
            );
            $input->setArgument('vid', $vid);
        }
    }

    /**
     * Destroy all existing terms
     *
     * @param $vid
     */
    private function deleteExistingTerms($vid = null)
    {
        $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
        //Load all vocabularies
        $vocabularies = $this->getVocabularies();

        if ($vid === 'all') {
            $vid = array_keys($vocabularies);
        } else {
            $vid = [$vid];
        }

        foreach ($vid as $item) {
            $vocabulary = $vocabularies[$item];
            $terms = $termStorage->loadTree($vocabulary->id());

            if (empty($terms)) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.taxonomy.term.delete.messages.nothing'),
                        $item
                    )
                );
            } else {
                foreach ($terms as $term) {
                    $treal = $termStorage->load($term->tid);

                    if ($treal !== null) {
                        $this->getIo()->info(
                            sprintf(
                                $this->trans('commands.taxonomy.term.delete.messages.processing'),
                                $term->name
                            )
                        );
                        $treal->delete();
                    }
                }
            }
        }
    }

    private function getVocabularies()
    {
        return $this->entityTypeManager->getStorage('taxonomy_vocabulary')
            ->loadMultiple();
    }
}
