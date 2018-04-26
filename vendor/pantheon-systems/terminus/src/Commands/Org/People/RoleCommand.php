<?php

namespace Pantheon\Terminus\Commands\Org\People;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class RoleCommand
 * @package Pantheon\Terminus\Commands\Org\People
 */
class RoleCommand extends TerminusCommand
{
    /**
     * Changes a user's role within an organization.
     *
     * @authorize
     *
     * @command org:people:role
     * @aliases org:ppl:role
     *
     * @param string $organization Organization name, label, or ID
     * @param string $member User UUID, email address, or full name
     * @param string $role [unprivileged|admin|team_member|developer] Role
     *
     * @usage <organization> <user> <role> Changes the role of the user, <user>, to <role> within <organization>.
     */
    public function role($organization, $member, $role)
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($organization)->getOrganization();
        $membership = $org->getUserMemberships()->fetch()->get($member);
        $workflow = $membership->setRole($role);
        while (!$workflow->checkProgress()) {
            // @TODO: Remove Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            "{member}'s role has been changed to {role} in the {org} organization.",
            [
                'member' => $membership->getUser()->getName(),
                'role' => $role,
                'org' => $org->getName(),
            ]
        );
    }
}
