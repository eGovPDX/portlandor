<?php

namespace Pantheon\Terminus\Commands\NewRelic;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class EnableCommand
 * @package Pantheon\Terminus\Commands\NewRelic
 */
class EnableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Enables New Relic for a site.
     *
     * @authorize
     *
     * @command new-relic:enable
     *
     * @param string $site_id Site name
     *
     * @usage <site> Enables New Relic for <site>.
     */
    public function enable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getNewRelic()->enable();
        $this->log()->notice('New Relic enabled. Converging bindings.');
        $workflow = $site->converge();
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
