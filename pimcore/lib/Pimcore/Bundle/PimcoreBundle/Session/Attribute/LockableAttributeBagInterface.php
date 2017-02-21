<?php

namespace Pimcore\Bundle\PimcoreBundle\Session\Attribute;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

interface LockableAttributeBagInterface extends AttributeBagInterface
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
