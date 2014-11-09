<?php

namespace CourseHero\SymfonyCronBundle\Tests\Component\Lock;

use \CourseHero\SymfonyCronBundle\Component\Lock\LockFileService;
use \CourseHero\SymfonyCronBundle\Component\Lock\LockServiceInterface;
use \PHPUnit_Framework_TestCase;
use \org\bovigo\vfs\vfsStream;

class LockFileServiceTest extends PHPUnit_Framework_TestCase
{
    private $lockFileService;

    private $tmpFile;
    private $tmpFileName;
    private $key2Handle;

    public function setUp()
    {
        vfsStream::setup('keys');

        $this->key2Handle = fopen(vfsStream::url('keys/2'), 'c');
        $this->assertNotFalse($this->key2Handle);
        $this->assertTrue(flock($this->key2Handle, LOCK_EX | LOCK_NB));

        fclose(fopen(vfsStream::url('keys/3'), 'c'));
        $this->assertTrue(
            chmod(vfsStream::url('keys/3'), 0200)
        );

        fclose(fopen(vfsStream::url('keys/4'), 'c'));
        $this->assertTrue(
            chmod(vfsStream::url('keys/4'), 0000)
        );

        $this->tmpFile = tmpfile();
        $this->lockFileService = new LockFileService();

        $metadata = stream_get_meta_data($this->tmpFile);
        $this->tmpFileName = $metadata['uri'];
    }

    public function tearDown()
    {
        flock($this->key2Handle, LOCK_UN);

        if (file_exists($this->tmpFileName)) {
            flock($this->tmpFile, LOCK_UN);
            fclose($this->tmpFile);
        }
    }

    /**
     * @dataProvider negativeDataForKeyTests
     * @expectedException \InvalidArgumentException
     */
    public function testLock_KeyNegativeTests($key)
    {
        $this->lockFileService->lock($key);
        $this->fail('Key should be have been considered valid');
    }

    public function negativeDataForKeyTests()
    {
        return array(
            array(null),
            array(''),
            array('file:/'),
            array('file://'),
            array('file:///'),
            array('/this/is/a/valid/path/missing/prefix'),
            array(' file:///this/is/a/valid/path/other/than/the/space'),
        );
    }

    /**
     * @dataProvider positiveDataForKeyTests
     */
    public function testLock_PositiveTests($key, $shouldGainLock, $mode)
    {
        $fileHandle = $this->lockFileService->lock($key);

        if ($shouldGainLock) {
            // Make sure that the file handle was returned and that the
            // file was locked -- tested by attempting to lock the file.
            $this->assertNotFalse($fileHandle);

            $metadata = stream_get_meta_data($fileHandle);
            $this->assertTrue(file_exists($metadata['uri']));

            $tmpHandle = fopen($key, $mode);
            $this->assertNotFalse($tmpHandle);
            $this->assertFalse(flock($tmpHandle, LOCK_EX | LOCK_NB));
        } else {
            // A lock should not have been gained.
            $this->assertFalse($fileHandle);
        }
    }

    public function positiveDataForKeyTests()
    {
        return array(
            array(vfsStream::url('keys/1'), TRUE,  'c' ),
            array(vfsStream::url('keys/2'), FALSE, null),
            array(vfsStream::url('keys/3'), TRUE,  'a' ),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLock_NoFilePermissions()
    {
        $this->lockFileService->lock(vfsStream::url('keys/4'));
        $this->fail('Should not be able to lock a file with invalid permissions');
    }

    public function testLock_RealFile()
    {
        $this->testLock_PositiveTests('file://' . $this->tmpFileName, TRUE, 'c');
    }

    public function testLock_RealFile_AlreadyLocked()
    {
        $this->assertTrue(
            flock($this->tmpFile, LOCK_EX | LOCK_NB)
        );

        $this->testLock_PositiveTests('file://' . $this->tmpFileName, FALSE, null);

        flock($this->tmpFile, LOCK_UN);
    }

    public function testUnlock_BooleanArg()
    {
        $this->assertFalse(
            $this->lockFileService->unlock(FALSE)
        );

        $this->assertFalse(
            $this->lockFileService->unlock(TRUE)
        );
    }

    /**
     * @dataProvider getDataForTestUnlockNotAResourceArg
     * @expectedException \InvalidArgumentException
     */
    public function testUnlock_NotAResourceArg($arg)
    {
        $this->lockFileService->unlock($arg);
        $this->fail('unlock() should not accept ' . $arg);
    }

    public function getDataForTestUnlockNotAResourceArg()
    {
        return array(
            array(null),
            array(''),
            array('1'),
            array('foobar'),
            array(1),
        );
    }

    public function testUnlock_LockedResource()
    {
        $this->assertTrue(
            $this->lockFileService->unlock(
                $this->key2Handle
            )
        );
    }

    /**
     * @depends testUnlock_LockedResource
     */
    public function testUnlock_UnlockedResource()
    {
        $this->testUnlock_LockedResource();
        $this->testUnlock_LockedResource();
    }
}
