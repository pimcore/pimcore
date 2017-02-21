<?php

namespace Pimcore\Bundle\PimcoreBundle\Session\Attribute;

use Pimcore\Bundle\PimcoreBundle\Session\Attribute\Exception\AttributeBagLockedException;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

class LockableAttributeBag extends AttributeBag implements LockableAttributeBagInterface
{
    /**
     * @var bool
     */
    protected $locked = false;

    /**
     * @inheritDoc
     */
    public function lock()
    {
        $this->locked = true;
    }

    /**
     * @inheritDoc
     */
    public function unlock()
    {
        $this->locked = false;
    }

    /**
     * @inheritDoc
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * @inheritDoc
     */
    public function set($name, $value)
    {
        $this->checkLock();

        parent::set($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function replace(array $attributes)
    {
        $this->checkLock();

        parent::replace($attributes);
    }

    /**
     * @inheritDoc
     */
    public function remove($name)
    {
        $this->checkLock();

        return parent::remove($name);
    }

    /**
     * @inheritDoc
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
