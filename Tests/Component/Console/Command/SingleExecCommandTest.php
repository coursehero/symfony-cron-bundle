<?php

namespace SymfonyCronBundle\Tests\Component\Console\Command;

use \SymfonyCronBundle\Component\Console\Command\SingleExecCommand;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use \Symfony\Component\Console\Application;
use \Symfony\Component\Console\Input\InputDefinition;

class SingleExecCommandTest extends KernelTestCase
{
    private $application;

    private $argv;

    public function setUp()
    {
        self::bootKernel();

        $this->application = new Application(static::$kernel);
        $this->application->setAutoExit(false);
        $this->application->add(new SingleExecCommand());

        $this->argv = array(SingleExecCommand::CMD_NAME);
    }

    public function testConfigure()
    {
        $this->assertEquals(
            'cron:single-exec',
            SingleExecCommand::CMD_NAME
        );

        $command = $this->application->find(SingleExecCommand::CMD_NAME);
        $inputDefinition = $command->getDefinition();

        $this->assertEquals(
            1,
            count($inputDefinition->getArguments())
        );

        $this->assertTrue(
            $inputDefinition->hasArgument('actual_command')
        );
        $this->assertTrue(
            $inputDefinition->getArgument('actual_command')->isRequired()
        );
        $this->assertTrue(
            $inputDefinition->getArgument('actual_command')->isArray()
        );

        $this->assertEquals(
            3,
            count($inputDefinition->getOptions())
        );

        $this->assertTrue(
            $inputDefinition->hasOption('id')
        );
        $this->assertTrue(
            $inputDefinition->getOption('id')->isValueRequired()
        );

        $this->assertTrue(
            $inputDefinition->hasOption('child_process')
        );
        $this->assertFalse(
            $inputDefinition->getOption('child_process')->acceptValue()
        );

        $this->assertTrue(
            $inputDefinition->hasOption('lock_service')
        );
        $this->assertTrue(
            $inputDefinition->getOption('lock_service')->isValueRequired()
        );
    }
}
