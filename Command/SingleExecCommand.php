<?php

namespace SymfonyCronBundle\Command;

use \SymfonyCronBundle\Component\Lock\LockServiceInterface;
use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use \Symfony\Component\Console\Input\ArgvInput;
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

    const DEFAULT_LOCK_SERVICE = 'symfony_cron.default_lock_service';
    const PROCESS_SERVICE = 'symfony_cron.process_service';

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
        if (!is_a($lockService, '\SymfonyCronBundle\Component\Lock\LockServiceInterface')) {
            $type =
                is_object($lockService)
                    ? get_class($lockService)
                    : gettype($lockService)
                ;
            throw new \UnexpectedValueException(
                'Lock service is of unexpected type: ' .
                $this->get_formatted_type($lockService)
            );
        }

        // Attempt to lock the key.  If we cannot get the key, the
        // assumption is that another instance of the same process is
        // already running.
        $handle = $lockService->lock($id);
        if ($handle === false) {
            throw new \OverflowException(
                "Unable to obtain lock on '$id'"
            );
        }

        // Run the $actualCommand, spawning a child process or loading
        // up a child command.
        if ($input->getOption(self::OPT_CHILD_PROCESS)) {
            $processService =
                $this->getContainer()->get(
                    self::PROCESS_SERVICE
                );
            if (!is_a($processService, '\SymfonyCronBundle\Component\Process\ProcessService')) {
                throw new \UnexpectedValueException(
                    'Process service is of unexpected type: ' .
                    $this->get_formatted_type($processService)
                );
            }

            $process =
                $processService->createProcess(
                    implode(' ', $actualCommand)
                );

            $returnCode =
                $process->run(function ($type, $buffer) use (&$output) {
                    if (Process::ERR === $type) {
                        $output->write("<error>$buffer</error>");
                    } else {
                        $output->write("<info>$buffer</info>");
                    }
                });
        } else {
            $childCommandName = $actualCommand[0];
            $childArguments =
                array(
                    'app/console', // just something as argv[0]
                );
            foreach ($actualCommand as $command) {
                $childArguments[] = $command;
            }
            $childCommand = $this->getApplication()->find($childCommandName);
            $returnCode =
                $childCommand->run(
                    new ArgvInput($childArguments),
                    $output
                );
        }

        // Unlock the key for the next instance.
        $lockService->unlock($handle);

        // Finally, return the result code from the $actualCommand.
        return $returnCode;
    }

    /**
     * Returns a well-formatted name, regardless of whether the thing is
     * an object or base type.
     *
     * @param mixed $thing
     * @return string the best name of the $thing possible
     */
    private function get_formatted_type($thing)
    {
        return
            is_object($thing)
                ? get_class($thing)
                : gettype($thing)
            ;
    }
}
