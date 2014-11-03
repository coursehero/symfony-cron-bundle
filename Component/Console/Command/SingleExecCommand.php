<?php

namespace SymfonyCronBundle\Component\Console\Command;

use \SymfonyCronBundle\Component\DependencyInjection\LockService;
use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Process\Process;

/**
 * A SingleExecCommand is a Symfony2 Command that ensures
 * single-instance execution by using a unique key and external lock
 * service to allow for mutual exclusion locking.
 *
 * @package  SymfonyCronBundle\Component\Console
 * @author   Chris Verges <cverges@coursehero.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache2
 * @link     http://github.com/course-hero/symfony-cron-bundle
 */
class SingleExecCommand extends ContainerAwareCommand
{
    const CMD_NAME = 'cron:single-exec';
    const ARG_ACTUAL_COMMAND = 'actual_command';
    const OPT_ID = 'id';
    const OPT_CHILD_PROCESS = 'child_process';
    const OPT_LOCK_SERVICE = 'lock_service';

    const AUTO_KEY_HASH_ALGO = 'sha256';

    const DEFAULT_LOCK_SERVICE = 'cron_single_exec_lock';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription(
                'Executes another Command inside a single-execution context'
            )
            ->addArgument(
                self::ARG_ACTUAL_COMMAND,
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'The actual command that will be executed inside a ' .
                'single-execution context.'
            )
            ->addOption(
                self::OPT_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'A unique id used to establish whether another ' .
                'execution is already running.',
                null
            )
            ->addOption(
                self::OPT_CHILD_PROCESS,
                null,
                InputOption::VALUE_NONE,
                'Spawn a new process before executing the command.  If ' .
                'given, the ' . self::ARG_ACTUAL_COMMAND . ' must start ' .
                'with the child process\' executable.',
                null
            )
            ->addOption(
                self::OPT_LOCK_SERVICE,
                null,
                InputOption::VALUE_REQUIRED,
                'The name of the service that will actually do the locking.',
                self::DEFAULT_LOCK_SERVICE
            )
            ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // First, setup the parsed arguments and options.
        $actualCommand = $input->getArgument(self::ARG_ACTUAL_COMMAND);

        $id = $input->getOption(self::OPT_ID);
        if (!$id) {
            // No id given, hash $actualCommand for something unique
            $id =
                hash(
                    self::AUTO_KEY_HASH_ALGO,
                    implode(' ', $actualCommand)
                );
        }

        // Next, grab the lock service.
        $lockService =
            $this->getContainer()->get(
                $input->getOption(self::OPT_LOCK_SERVICE)
            );
        if (!($lockService instanceof LockService)) {
            throw new \UnexpectedValueException(
                'Lock service is of unexpected type: ' .
                get_class($lockService)
            );
        }

        // Attempt to lock the key.  If we cannot get the key, the
        // assumption is that another instance of the same process is
        // already running.
        if ($lockService->lock($key) != true) {
            throw new \OverflowException(
                "Unable to obtain lock on '$key'"
            );
        }

        // Run the $actualCommand, spawning a child process or loading
        // up a child command.
        if ($input->hasOption(self::OPT_CHILD_PROCESS)) {
            $process = new Process(implode(' ', $actualCommand));
            $returnCode =
                $process->run(function ($type, $buffer) {
                    if (Process::ERR === $type) {
                        $output->write("<error>$buffer</error>");
                    } else {
                        $output->write("<info>$buffer</info>");
                    }
                });
        } else {
            $childArguments = $actualCommand;
            $childCommandName = array_shift($childArguments);
            $childCommand = $this->getApplication()->find($childCommandName);
            $returnCode =
                $childCommand->run(
                    new ArgvInput($childArguments),
                    $output
                );
        }

        // Unlock the key for the next instance.
        $lockService->unlock($key);

        // Finally, return the result code from the $actualCommand.
        return $returnCode;
    }
}
