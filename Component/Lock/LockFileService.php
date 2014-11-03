<?php

namespace SymfonyCronBundle\Component\Lock;

/**
 * The LockFileService uses filesystem-based locking, where the key is
 * actually a file path.  If necessary, the file is created.  To lock
 * the file, the flock() function is called.
 * @see PHP_MANUAL#flock
 *
 * To accomplish this, an exclusive, non-blocking lock is required.
 *
 * Some platforms (notably some versions of Windows) do not support the
 * non-blocking requirement.  Some users have reported success with
 * this, so test on your intended target before using in production.
 * @see http://us3.php.net/flock#101701
 *
 * Also, exclusive locks across network-based filesystems are only
 * possible if the underlying filesystem supports this.  Again, please
 * test on your intended target before using in production.
 *
 * @package  SymfonyCronBundle\Component\Lock
 * @author   Chris Verges <cverges@coursehero.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache2
 */
class LockFileService implements LockServiceInterface
{
    /**
     * Validates the provided key is something valid.  A valid key is
     * considered to be anything that matches the following format:
     *
     * <code>file://<i>/path/to/key/file</i></code>
     *
     * Note: for unit testing purposes, matches with <code>vfs://</code>
     * are also allowed due to the use of vfsStream.
     *
     * @param string $key
     * @throws \InvalidArgumentException if the key is invalid
     */
    private function validateKey($key)
    {
        $file_pattern =
            '(' .
                preg_quote('file:///', '/') .
            '|' .
                preg_quote('vfs://', '/') .
            ')';

        $pattern = '/^' . $file_pattern . '.+/i';

        if (preg_match($pattern, $key) == FALSE) {      // FALSE or 0 intended
            throw new \InvalidArgumentException('Key must be a valid file:// URL');
        }
    }

    /**
     * Attempts to open a file handle to the key (as a file resource).
     * If the file does not exist, an attempt is made to create it.  If
     * the file already exists, both read and write access are
     * individually attempted.
     *
     * @param string $key
     * @return resource the file handle
     * @throws \InvalidArgumentException if the key is invalid or the
     *                                   file could not be opened
     *                                   successfully
     */
    private function getFileHandle($key)
    {
        $this->validateKey($key);

        if (!file_exists($key)) {
            $mode = 'c+'; // attempt to create with read/write access
        } else if (is_readable($key)) {
            $mode = 'r';  // attempt for read-only access
        } else if (is_writable($key)) {
            $mode = 'a';  // attempt for write-only access
        } else {
            throw new \InvalidArgumentException("Cannot access $key: permission denied");
        }

        $fd = fopen($key, $mode);
        if ($fd === FALSE) {
            throw new \InvalidArgumentException("Cannot open file using mode '$mode': $key");
        }

        return $fd;
    }

    /**
     * @inheritDoc
     */
    public function lock($key)
    {
        $fileHandle = $this->getFileHandle($key);
        if (flock($fileHandle, LOCK_EX | LOCK_NB)) {
            // Do not close $fileHandle, as this could release the lock
            // on certain platforms.
            return $fileHandle;
        } else {
            fclose($fileHandle);
            return FALSE;
        }
    }

    /**
     * @inheritDoc
     */
    public function unlock($lockReturnValue)
    {
        if (is_bool($lockReturnValue)) {
            // This is benign, just move on
            return FALSE;
        }

        if (!is_resource($lockReturnValue)) {
            throw new \InvalidArgumentException('unlock() requires a resource as returned by lock()');
        }

        return flock($lockReturnValue, LOCK_UN);
    }
}
