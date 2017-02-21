<?php

namespace Pimcore\Bundle\PimcoreBundle\Session\Attribute;

interface LockableAttributeBagInterface
{
    /**
     * Lock the attribute bag (disallow modifications)
     */
    public function lock();

    /**
     * Unlock the attribute bag
     */
    public function unlock();

    /**
     * Get lock status
     *
     * @return bool
     */
    public function isLocked();
}
