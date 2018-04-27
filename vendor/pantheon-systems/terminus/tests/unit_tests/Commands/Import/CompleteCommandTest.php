<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Commands\Import\CompleteCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class CompleteCommandTest
 * Testing class for Pantheon\Terminus\Commands\Import\CompleteCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 */
class CompleteCommandTest extends CommandTestCase
{

    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();

        $this->command = new CompleteCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }
    
    /**
     * Tests the import:complete command
     */
    public function testSiteImportValidURL()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site_name = 'site_name';
        $this->site->expects($this->once())
            ->method('completeMigration')
            ->with()
            ->willReturn($workflow);
        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);
        $workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('The import of {site} has been marked as complete.'),
                $this->equalTo(['site' => $site_name,])
            );

        $out = $this->command->complete($site_name);
        $this->assertNull($out);
    }
}
