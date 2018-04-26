<?php

namespace Pantheon\Terminus\Commands\PaymentMethod;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\PaymentMethod
 */
class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Associates an existing payment method with a site.
     *
     * @authorize
     *
     * @command payment-method:add
     * @aliases pm:add
     *
     * @param string $site_name Site name
     * @param string $payment_method Payment method label or UUID
     *
     * @usage <site> <payment_method> Associates <payment_method> with <site>.
     */
    public function add($site_name, $payment_method)
    {
        $site = $this->getSite($site_name);
        $pm = $this->session()->getUser()->getPaymentMethods()->fetch()->get($payment_method);
        $workflow = $site->addPaymentMethod($pm->id);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }

        $this->log()->notice(
            '{method} has been applied to the {site} site.',
            ['method' => $pm->get('label'), 'site' => $site->get('name'),]
        );
    }
}
