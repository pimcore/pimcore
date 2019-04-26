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
 * @package    Object\Fieldcollection
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Fieldcollection\Data;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\Fieldcollection\Data\Dao getDao()
 */
abstract class AbstractData extends Model\AbstractModel
{

    /**
     * @var int
     */
    public $index;

    /**
     * @var string
     */
    public $fieldname;

    /**
     * @var Model\Object\Concrete
     */
    public $object;

    /**
     * @var string
     */
    public $type;

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int $index
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = (int) $index;

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldname()
    {
        return $this->fieldname;
    }

    /**
     * @param $fieldname
     * @return $this
     */
    public function setFieldname($fieldname)
    {
        $this->fieldname = $fieldname;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefinition()
    {
        $definition = Model\Object\Fieldcollection\Definition::getByKey($this->getType());

        return $definition;
    }

    /**
     * @param Model\Object\Concrete $object
     * @return $this
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return Model\Object\Concrete
     */
    public function getObject()
    {
        return $this->object;
    }
}
