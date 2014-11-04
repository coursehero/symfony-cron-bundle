<?php

namespace SymfonyCronBundle\Tests\Command;

use \SymfonyCronBundle\Command\SingleExecCommand;
use \SymfonyCronBundle\Component\Lock\LockServiceInterface;
use \SymfonyCronBundle\Component\Process\ProcessService;
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
    private $testProcessService;

    private $argv;

    public function setUp()
    {
        self::bootKernel();

        $this->testCommand = new TestCommand();

        $this->alwaysLockService = new AlwaysLockService();
        $this->testProcessService = new TestProcessService();

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
     * @dataProvider dataProviderForTestExecute_LockServiceTests
     */
    public function testExecute_LockServiceTests($expectedException, $lockService, $message)
    {
        $this->setExpectedException($expectedException);

        $this->argv[] = 'test:command';

        $input = new ArgvInput($this->argv);
        $output = new BufferedOutput();

        static::$kernel->getContainer()->set(
            SingleExecCommand::DEFAULT_LOCK_SERVICE,
            $lockService
        );

        $returnCode = $this->application->run($input, $output);
        $this->fail($message);
    }

    public function dataProviderForTestExecute_LockServiceTests()
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
        $output = new BufferedOutput();

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

    public function testExecute_RunAsChildApplication()
    {
        $this->argv[] = '--' . SingleExecCommand::OPT_CHILD_PROCESS;
        $this->argv[] = '--';
        $this->argv[] = 'php';
        $this->argv[] = 'app/console';
        $this->argv[] = 'test:command';
        $this->argv[] = '--option';
        $this->argv[] = '123';
        $this->argv[] = '456';

        $input = new ArgvInput($this->argv);
        $output = new BufferedOutput();

        static::$kernel->getContainer()->set(
            SingleExecCommand::PROCESS_SERVICE,
            $this->testProcessService
        );

        static::$kernel->getContainer()->set(
            SingleExecCommand::DEFAULT_LOCK_SERVICE,
            $this->alwaysLockService
        );

        $this->assertEquals(0, $this->alwaysLockService->numLocks);
        $this->assertEquals(0, $this->alwaysLockService->numUnlocks);
        $this->assertNull($this->testProcessService->testProcess);

        $returnCode = $this->application->run($input, $output);

        $this->assertNotNull($this->testProcessService->testProcess);
        $this->assertEquals(
            'php app/console test:command --option 123 456',
            $this->testProcessService->testProcess->getCommandLine()
        );
        $this->assertEquals(
            'some problemsome feedback', // assuming <error> and <info> OK
            $output->fetch()
        );
        $this->assertEquals(321, $returnCode);
        $this->assertEquals(1, $this->alwaysLockService->numLocks);
        $this->assertEquals(1, $this->alwaysLockService->numUnlocks);
    }

    /**
     * @dataProvider dataProviderForTestExecute_ProcessServiceTests
     */
    public function testExecute_ProcessServiceTests($expectedException, $processService, $message)
    {
        $this->setExpectedException($expectedException);

        $this->argv[] = '--' . SingleExecCommand::OPT_CHILD_PROCESS;
        $this->argv[] = '--';
        $this->argv[] = 'php';
        $this->argv[] = 'app/console';
        $this->argv[] = 'test:command';
        $this->argv[] = '--option';
        $this->argv[] = '123';
        $this->argv[] = '456';

        $input = new ArgvInput($this->argv);
        $output = new BufferedOutput();

        static::$kernel->getContainer()->set(
            SingleExecCommand::PROCESS_SERVICE,
            $processService
        );

        static::$kernel->getContainer()->set(
            SingleExecCommand::DEFAULT_LOCK_SERVICE,
            $this->alwaysLockService
        );

        $returnCode = $this->application->run($input, $output);
        $this->fail($message);
    }

    public function dataProviderForTestExecute_ProcessServiceTests()
    {
        return [
            [
                '\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException',
                null,
                'Process service must exist to run a child process',
            ],
            [
                '\UnexpectedValueException',
                0,
                'Process service must be a type of ProcessService',
            ],
            [
                '\UnexpectedValueException',
                'You may test that assumption at your convenience.',
                'Process service must be a type of ProcessService',
            ],
            [
                '\UnexpectedValueException',
                new \ReflectionClass('\Exception'),
                'Process service must be a type of ProcessService',
            ],
        ];
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

class TestProcessService extends ProcessService
{
    public $testProcess;

    public function createProcess($commandLine, $cwd = null, array $env = null, $input = null, $timeout = 60, array $options = array())
    {
        if (!isset($this->testProcess)) {
            $this->testProcess =
                new TestProcess(
                    $commandLine,
                    $cwd,
                    $env,
                    $input,
                    $timeout,
                    $options
                );
        }
        return $this->testProcess;
    }
}

class TestProcess extends Process
{
    public $callback;

    public function run($callback = null)
    {
        $this->callback = $callback;
        call_user_func($callback, Process::ERR, 'some problem');
        call_user_func($callback, Process::OUT, 'some feedback');
        return 321;
    }
}
