<?php

namespace SymfonyCronBundle\Component\Process;

use \Symfony\Component\Process\Process;

/**
 * An intermediate, mockable service for running Symfony2 Processes.
 *
 * @package  SymfonyCronBundle\Component\Process
 * @author   Chris Verges <cverges@coursehero.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache2
 * @link     http://github.com/course-hero/symfony-cron-bundle
 */
class ProcessService
{
    /**
     * Creates a new Symfony2 Process based on the input parameters.
     * This is mostly here to support mocking during unit testing.
     *
     * @param string             $commandline The command line to run
     * @param string|null        $cwd         The working directory or null to use the working dir of the current PHP process
     * @param array|null         $env         The environment variables or null to inherit
     * @param string|null        $input       The input
     * @param int|float|null     $timeout     The timeout in seconds or null to disable
     * @param array              $options     An array of options for proc_open
     *
     * @throws RuntimeException When proc_open is not installed
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Process/Process.php
     */
    public function createProcess($commandLine, $cwd = null, array $env = null, $input = null, $timeout = 60, array $options = array())
    {
        return new Process(
            $commandLine,
            $cwd,
            $env,
            $input,
            $timeout,
            $options
        );
    }
}
