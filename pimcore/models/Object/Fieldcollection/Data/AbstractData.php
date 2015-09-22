<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object\Fieldcollection
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\Fieldcollection\Data;

use Pimcore\Model;

abstract class AbstractData extends Model\AbstractModel {

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
    public function getIndex () {
        return $this->index;
    }

    /**
     * @param int $index
     * @return void
     */
    public function setIndex ($index) {
        $this->index = (int) $index;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldname () {
        return $this->fieldname;
    }

    /**
     * @param $fieldname
     * @return void
     */
    public function setFieldname ($fieldname) {
        $this->fieldname = $fieldname;
        return $this;
    }

    /**
     * @return string
     */
    public function getType () {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefinition () {
        $definition = Model\Object\Fieldcollection\Definition::getByKey($this->getType());
        return $definition;
    }

    /**
     * @param Object\Concrete $object
     * @return void
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return Object\Concrete
     */
    public function getObject()
    {
        return $this->object;
    }
}
