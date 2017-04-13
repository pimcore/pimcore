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
 * @category   Pimcore
 * @package    Tool
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Targeting;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\Targeting\Persona\Dao getDao()
 */
class Persona extends Model\AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var int
     */
    public $threshold = 1;

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var array
     */
    public $conditions = [];

    /**
     * @param $id
     *
     * @return null|Persona
     */
    public static function getById($id)
    {
        try {
            $persona = new self();
            $persona->setId(intval($id));
            $persona->getDao()->getById();

            return $persona;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * add the persona to the current user
     *
     * @param $id
     */
    public static function fire($id)
    {
        $targetingService = \Pimcore::getContainer()->get('pimcore.event_listener.frontend.targeting');
        $targetingService->addPersona($id);
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public static function isIdActive($id)
    {
        $persona = Model\Tool\Targeting\Persona::getById($id);
        if ($persona) {
            return $persona->getActive();
        }

        return false;
    }

    /**
     * @param $description
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
     * @param $id
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
     * @param $name
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
     * @param $conditions
     *
     * @return $this
     */
    public function setConditions($conditions)
    {
        if (!$conditions) {
            $conditions = [];
        }
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
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
