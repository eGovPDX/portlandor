<?php


namespace Pantheon\Terminus\UnitTests\Commands\Lock;

use Pantheon\Terminus\Commands\Lock\DisableCommand;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class DisableCommandTest
 * Testing class for Pantheon\Terminus\Commands\Lock\DisableCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Lock
 */
class DisableCommandTest extends LockCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new DisableCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }
    /**
     * Tests the lock:disable command
     */
    public function testDisable()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site_name = 'site_name';
        $this->environment->id = 'env_id';
        $this->lock->expects($this->once())
            ->method('disable')
            ->with()
            ->willReturn($workflow);
        $workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{site}.{env} has been unlocked.'),
                $this->equalTo(['site' => $site_name, 'env' => $this->environment->id,])
            );

        $out = $this->command->disable("$site_name.{$this->environment->id}");
        $this->assertNull($out);
    }
}
