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

namespace Pimcore\Model\Tool\Targeting;

use Pimcore\Model;

/**
 * @internal
 *
 * @method TargetGroup\Dao getDao()
 * @method void delete()
 */
class TargetGroup extends Model\AbstractModel
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var int
     */
    protected $threshold = 1;

    /**
     * @var bool
     */
    protected $active = true;

    /**
     * @param int $id
     *
     * @return null|TargetGroup
     */
    public static function getById($id)
    {
        try {
            $targetGroup = new self();
            $targetGroup->getDao()->getById((int)$id);

            return $targetGroup;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $name
     *
     * @return TargetGroup|null
     *
     * @throws \Exception
     */
    public static function getByName($name)
    {
        try {
            $target = new self();
            $target->getDao()->getByName($name);

            return $target;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public static function isIdActive($id)
    {
        $targetGroup = Model\Tool\Targeting\TargetGroup::getById($id);

        if ($targetGroup) {
            return $targetGroup->getActive();
        }

        return false;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $threshold
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;
    }

    /**
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }
}
