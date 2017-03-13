<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

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
