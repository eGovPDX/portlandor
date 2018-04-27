<?php

namespace Pantheon\Terminus\UnitTests\Commands\Remote;

use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\ProcessUtils;

/**
 * SSHBaseCommand Test Suite
 * Testing class for Pantheon\Terminus\Commands\Remote\SSHBaseCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Remote
 */
class SSHBaseCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new DummyCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests command execution under normal circumstances
     */
    public function testExecuteCommand()
    {
        $dummy_output = 'dummy output';
        $options = ['arg1', 'arg2', '<escape me>',];
        $site_name = 'site name';
        $mode = 'sftp';

        $this->environment->expects($this->once())
            ->method('get')
            ->with($this->equalTo('connection_mode'))
            ->willReturn($mode);
        $this->site->expects($this->any())->method('get')
            ->withConsecutive(
                [$this->equalTo('name'),],
                [$this->equalTo('name'),]
            )
            ->willReturnOnConsecutiveCalls($site_name, $site_name);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Command: {site}.{env} -- {command} [Exit: {exit}]')
            );
        $this->environment->expects($this->once())
            ->method('sendCommandViaSsh')
            ->willReturn(['output' => $dummy_output, 'exit_code' => 0,]);

        $out = $this->command->dummyCommand("$site_name.env", $options);
        $this->assertNull($out);
    }

    /**
     * Tests command execution when exiting with a nonzero status
     */
    public function testExecuteCommandNonzeroStatus()
    {
        $dummy_output = 'dummy output';
        $options = ['arg1', 'arg2',];
        $site_name = 'site name';
        $mode = 'sftp';
        $status_code = 1;
        $this->environment->id = 'env_id';
        $command = 'dummy ' . implode(' ', $options);

        $expectedLoggedCommand = 'dummy arg1 arg2';

        $this->environment->expects($this->once())
            ->method('get')
            ->with($this->equalTo('connection_mode'))
            ->willReturn($mode);
        $this->site->expects($this->any())->method('get')
            ->withConsecutive(
                [$this->equalTo('name'),],
                [$this->equalTo('name'),]
            )
            ->willReturnOnConsecutiveCalls($site_name, $site_name);
        $this->environment->expects($this->once())
            ->method('sendCommandViaSsh')
            ->with($this->equalTo($command))
            ->willReturn(['output' => $dummy_output, 'exit_code' => $status_code,]);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Command: {site}.{env} -- {command} [Exit: {exit}]'),
                $this->equalTo([
                    'site' => $site_name,
                    'env' => $this->environment->id,
                    'command' => "$expectedLoggedCommand",
                    'exit' => $status_code,
                ])
            );

        $this->setExpectedException(TerminusProcessException::class, $dummy_output);

        $out = $this->command->dummyCommand("$site_name.{$this->environment->id}", $options);
        $this->assertNull($out);
    }

    /**
     * Tests command execution when in git mode
     */
    public function testExecuteCommandInGitMode()
    {
        $dummy_output = 'dummy output';
        $options = ['arg1', 'arg2', '--secret', 'somesecret'];
        $site_name = 'site name';
        $mode = 'git';
        $status_code = 0;
        $command = 'dummy ' . implode(' ', $options);

        $expectedLoggedCommand = 'dummy arg1 arg2';

        $this->environment->expects($this->once())
            ->method('get')
            ->with($this->equalTo('connection_mode'))
            ->willReturn($mode);
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('warning'),
                $this->equalTo(
                    'This environment is in read-only Git mode. If you want to make changes to the codebase of this site '
                    . '(e.g. updating modules or plugins), you will need to toggle into read/write SFTP mode first.'
                )
            );
        $this->site->expects($this->any())->method('get')
            ->withConsecutive(
                [$this->equalTo('name'),],
                [$this->equalTo('name'),]
            )
            ->willReturnOnConsecutiveCalls($site_name, $site_name);
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Command: {site}.{env} -- {command} [Exit: {exit}]'),
                $this->equalTo([
                    'site' => $site_name,
                    'env' => $this->environment->id,
                    'command' => "$expectedLoggedCommand",
                    'exit' => $status_code,
                ])
            );
        $this->environment->expects($this->once())
            ->method('sendCommandViaSsh')
            ->with($this->equalTo('dummy ' . implode(' ', $options)))
            ->willReturn(['output' => $dummy_output, 'exit_code' => $status_code,]);

        $out = $this->command->dummyCommand("$site_name.env", $options);
        $this->assertNull($out);
    }

    /**
     * Tests the SSHBaseCommand::useTty(InputInterface) command when in interactive mode
     */
    public function testUseTtyInteractive()
    {
        $input = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $input->expects($this->once())
            ->method('isInteractive')
            ->with()
            ->willReturn(true);
        $useTty = $this->command->useUseTty($input);
        $this->assertTrue(in_array($useTty, [false, null,]));
    }

    /**
     * Tests the SSHBaseCommand::useTty(InputInterface) command when not in interactive mode
     */
    public function testUseTtyNoninteractive()
    {
        $input = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $input->expects($this->once())
            ->method('isInteractive')
            ->with()
            ->willReturn(false);
        $this->assertFalse($this->command->useUseTty($input));
    }
}
