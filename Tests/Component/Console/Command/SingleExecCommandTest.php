<?php

namespace SymfonyCronBundle\Tests\Component\Console\Command;

use \SymfonyCronBundle\Component\Console\Command\SingleExecCommand;
use \SymfonyCronBundle\Component\Lock\LockServiceInterface;
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
    private $testCommand;
    private $alwaysLockService;

    private $argv;

    public function setUp()
    {
        self::bootKernel();

        $this->testCommand = new TestCommand();

        $this->alwaysLockService = new AlwaysLockService();

        $this->application = new Application(static::$kernel);
        $this->application->setCatchExceptions(false);
        $this->application->setAutoExit(false);
        $this->application->add(new SingleExecCommand());
        $this->application->add($this->testCommand);

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
     * @dataProvider dataProviderForTestExecute
     */
    public function testExecute($expectedException, $lockService, $message)
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

    public function dataProviderForTestExecute()
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
                'Objects not of the LockServiceInterface type should not be allowed',
            ],
            [
                '\OverflowException',
                new NeverLockService(),
                'An exception should be thrown if the lock cannot be obtained',
            ],
        ];
    }

    public function testExecute_RunAsEmbeddedApplication()
    {
        $this->argv[] = '--';
        $this->argv[] = 'test:command';
        $this->argv[] = '--option';
        $this->argv[] = '123';
        $this->argv[] = '456';

        $input = new ArgvInput($this->argv);
        $output = new ConsoleOutput();

        // Ensure the service is unset
        static::$kernel->getContainer()->set(
            SingleExecCommand::DEFAULT_LOCK_SERVICE,
            $this->alwaysLockService
        );

        $this->testCommand->returnCode = 123;

        $this->assertEquals(0, $this->alwaysLockService->numLocks);
        $this->assertEquals(0, $this->alwaysLockService->numUnlocks);

        $returnCode = $this->application->run($input, $output);

        $this->assertEquals(123, $returnCode);
        $this->assertEquals(1, $this->alwaysLockService->numLocks);
        $this->assertEquals(1, $this->alwaysLockService->numUnlocks);
        $this->assertEquals('123', $this->testCommand->option);
        $this->assertEquals(1, count($this->testCommand->argument));
        $this->assertEquals('456', $this->testCommand->argument[0]);
    }
}

class AlwaysLockService implements LockServiceInterface
{
    public $numLocks = 0;
    public $numUnlocks = 0;

    public function lock($key)
    {
        $this->numLocks++;
        return true;
    }

    public function unlock($resource)
    {
        $this->numUnlocks++;
        return true;
    }
}

class NeverLockService implements LockServiceInterface
{
    public $numLocks = 0;
    public $numUnlocks = 0;

    public function lock($key)
    {
        $this->numLocks++;
        return false;
    }

    public function unlock($resource)
    {
        $this->numUnlocks++;
        return false;
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
