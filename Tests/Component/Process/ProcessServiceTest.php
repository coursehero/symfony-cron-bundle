<?php

namespace CourseHero\SymfonyCronBundle\Tests\Component\Process;

use \CourseHero\SymfonyCronBundle\Component\Process\ProcessService;
use \PHPUnit_Framework_TestCase;

class SingleExecCommandTest extends PHPUnit_Framework_TestCase
{
    private $processService;

    private $commandLine = 'test-command-line';
    private $cwd = '/some/working/directory';
    private $env = array(
        'var 1' => 1,
        'var 2' => 2,
        'var 3' => 3,
    );
    private $input = 'STDIN';
    private $timeout = 180;
    private $options = array(
        'suppress_errors' => true,
        'bypass_shell'    => true,
        'binary_pipes'    => false,
    );

    public function setUp()
    {
        $this->processService = new ProcessService();
    }

    public function testCreateProcess()
    {
        $process =
            $this->processService->createProcess(
                $this->commandLine,
                $this->cwd,
                $this->env,
                $this->input,
                $this->timeout,
                $this->options
            );

        $this->assertNotNull($process);
        $this->assertInstanceOf(
            '\Symfony\Component\Process\Process',
            $process
        );
        $this->assertEquals(
            $this->commandLine,
            $process->getCommandLine()
        );
        $this->assertEquals(
            $this->cwd,
            $process->getWorkingDirectory()
        );
        $this->assertEquals(
            $this->env,
            $process->getEnv()
        );
        $this->assertEquals(
            $this->input,
            $process->getInput()
        );
        $this->assertEquals(
            $this->timeout,
            $process->getTimeout()
        );
        $this->assertEquals(
            $this->options,
            $process->getOptions()
        );
    }
}
