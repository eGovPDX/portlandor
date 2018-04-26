<?php

namespace Pantheon\Terminus\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\PropertyList;

/**
 * Class LookupCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class LookupCommand extends SiteCommand
{
    /**
     * Displays the UUID of a site given its name.
     *
     * @authorize
     *
     * @command site:lookup
     *
     * @field-labels
     *     id: ID
     *     name: Name
     * @default-string-field id
     * @return PropertyList
     *
     * @param string $site_name Name of a site to look up
     *
     * @usage <site> Displays the UUID of <site> if it exists and is accessible to you.
     */
    public function lookup($site_name)
    {
        return new PropertyList($this->sites()->get($site_name)->serialize());
    }
}
