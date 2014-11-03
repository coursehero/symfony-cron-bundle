<?php

namespace SymfonyCronBundle\Tests\Component\Console\Command;

use \SymfonyCronBundle\Component\Console\Command\SingleExecCommand;
use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use \Symfony\Bundle\FrameworkBundle\Console\Application;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use \Symfony\Component\Console\Input\ArgvInput;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\BufferedOutput;
use \Symfony\Component\Console\Output\ConsoleOutput;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use \Symfony\Component\Process\Process;

class SingleExecCommandTest extends KernelTestCase
{
    private $application;

    private $argv;

    public function setUp()
    {
        self::bootKernel();

        $this->application = new Application(static::$kernel);
        $this->application->setCatchExceptions(false);
        $this->application->setAutoExit(false);
        $this->application->add(new SingleExecCommand());
        $this->application->add(new TestCommand());

        $this->argv =
            array(
                'app/console', // this needs to be first
                SingleExecCommand::CMD_NAME
            );
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

    /**
     * @dataProvider dataProviderForExecute_BadLockService
     */
    public function testExecute_BadLockService($expectedException, $lockService, $message)
    {
        $this->setExpectedException($expectedException);

        $this->argv[] = 'test:command';

        $input = new ArgvInput($this->argv);
        $output = new ConsoleOutput();

        // Ensure the service is unset
        static::$kernel->getContainer()->set(
            SingleExecCommand::DEFAULT_LOCK_SERVICE,
            $lockService
        );

        $returnCode = $this->application->run($input, $output);
        $this->fail($message);
    }

    public function dataProviderForExecute_BadLockService()
    {
        return [
            [
                '\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException',
                null,
                'Non-existant lock_service should not be allowed by Symfony2 kernel',
            ],
            [
                '\UnexpectedValueException',
                0,
                'Non-object lock_service should not be allowed',
            ],
            [
                '\UnexpectedValueException',
                'If we\'re going to be damned, let\'s be damned for what we really are.',
                'Non-object lock_service should not be allowed',
            ],
            [
                '\UnexpectedValueException',
                new \ReflectionClass('\Exception'),
                'Objects not of the LockService type should not be allowed',
            ],
        ];
    }
}

class TestCommand extends ContainerAwareCommand
{
    public $exceptionToThrow;
    public $returnCode;
    public $argument;
    public $option;

    public function configure()
    {
        $this
            ->setName('test:command')
            ->setDescription(
                'This is a test command'
            )
            ->addArgument(
                'argument',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Just a sample argument'
            )
            ->addOption(
                'option',
                null,
                InputOption::VALUE_REQUIRED,
                'Something',
                null
            )
            ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->argument = $input->getArgument('argument');
        $this->option = $input->getOption('option');

        if (isset($this->exceptionToThrow) && $this->exceptionToThrow != null) {
            throw $this->exceptionToThrow;
        }

        return $this->returnCode;
    }
}
