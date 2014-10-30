<?php

namespace SymfonyCron\Console;

use \Symfony\Component\Console\Application;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * A Symfony2 application wrapper that implements single execution
 * protection.  The actual mechanism used is configurable as a service
 * interface.
 *
 * @package  SymfonyCron\Console
 * @author   Chris Verges <cverges@coursehero.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache2
 * @link     http://github.com/course-hero/symfony-cron-bundle
 */
class SingleExecApplication extends Application
{
    /**
     * @inheritDoc
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        return 16; // EBUSY
    }
}
