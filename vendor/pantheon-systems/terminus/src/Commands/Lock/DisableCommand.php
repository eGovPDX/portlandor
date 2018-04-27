<?php

namespace Pantheon\Terminus\Commands\Lock;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DisableCommand
 * @package Pantheon\Terminus\Commands\Lock
 */
class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Disables HTTP basic authentication on the environment.
     *
     * @authorize
     *
     * @command lock:disable
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Disables HTTP basic authentication on <site>'s <env> environment.
     */
    public function disable($site_env)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->getLock()->disable();
        while (!$workflow->checkProgress()) {
            // @TODO: Remove Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            '{site}.{env} has been unlocked.',
            ['site' => $site->get('name'), 'env' => $env->id,]
        );
    }
}
