<?php

namespace SymfonyCronBundle\Component\Lock;

/**
 * LockServiceInterface defines a mechanism for establishing a mutual
 * exclusion lock on some common resource.  Implementations use
 * different mechanisms for accomplishing this goal.
 *
 * @package  SymfonyCronBundle\Component\Lock
 * @author   Chris Verges <cverges@coursehero.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache2
 */
interface LockServiceInterface
{
    /**
     * Attempt to obtain an exclusive lock on some common $key.  The
     * actual format requirements behind the $key are dependent on the
     * particular implementation of LockServiceInterface.
     *
     * @param string $key the common key
     * @return mixed file handle if the lock was obtained, false if not
     * @throws \InvalidArgumentException if the key is invalid
     */
    public function lock($key);

    /**
     * Releases a previously obtained lock.  The caller is responsible
     * for ensuring that the lock was successfully obtained before
     * calling this function.
     *
     * @param mixed $lockReturnValue returned by lock()
     * @return boolean true if the operation was successful, false
     *                 otherwise
     * @throws \InvalidArgumentException if the key is invalid
     */
    public function unlock($lockReturnValue);
}
