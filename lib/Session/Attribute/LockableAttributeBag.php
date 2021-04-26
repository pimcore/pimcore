<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Session\Attribute;

use Pimcore\Session\Attribute\Exception\AttributeBagLockedException;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

class LockableAttributeBag extends AttributeBag implements LockableAttributeBagInterface
{
    /**
     * @var bool
     */
    protected $locked = false;

    /**
     * {@inheritdoc}
     */
    public function lock()
    {
        $this->locked = true;
    }

    /**
     * {@inheritdoc}
     */
    public function unlock()
    {
        $this->locked = false;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        $this->checkLock();

        parent::set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $attributes)
    {
        $this->checkLock();

        parent::replace($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        $this->checkLock();

        return parent::remove($name);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->checkLock();

        return parent::clear();
    }

    /**
     * @throws AttributeBagLockedException
     *      if lock is set
     */
    protected function checkLock()
    {
        if ($this->locked) {
            throw new AttributeBagLockedException('Attribute bag is locked');
        }
    }
}
