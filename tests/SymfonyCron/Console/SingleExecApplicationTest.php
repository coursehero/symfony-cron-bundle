<?php

namespace SymfonyCron\Tests\Console;

use \SymfonyCron\Console\SingleExecApplication;

use \PHPUnit_Framework_TestCase;
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
class SingleExecApplicationTest extends PHPUnit_Framework_TestCase
{
    private $app;

    private $name = 'TestApplicatoin';
    private $version = '1.2.3';

    public function setUp()
    {
        $this->app = new SingleExecApplication($this->name, $this->version);
    }

    public function testConstructor()
    {
        $this->assertEquals(
            $this->name,
            $this->app->getName()
        );

        $this->assertEquals(
            $this->version,
            $this->app->getVersion()
        );
    }

    public function testRun()
    {
        $this->assertEquals(
            16,
            $this->app->run()
        );
    }
}
