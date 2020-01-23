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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

trait DefaultValueTrait
{
    /**
     * @return mixed
     */
    abstract protected function doGetDefaultValue();

    /**
     * @param mixed $data
     * @param Concrete $object
     * @param array $params
     *
     * @return mixed modified data
     */
    protected function handleDefaultValue($data, $object = null, $params = [])
    {
        $isUpdate = isset($params['isUpdate']) ? $params['isUpdate'] : true;

        /**
         * 1. only for create, not on update. otherwise there is no way to null it out anymore.
         */
        if ($isUpdate) {
            return $data;
        }

        /**
         * 2. if inheritance is enabled and there is no parent value then take the default value.
         * 3. if inheritance is disabled, take the default value.
         */
        if ($this->isEmpty($data) && $this->doGetDefaultValue()) {
            $class = null;
            $owner = isset($params['owner']) ? $params['owner'] : null;
            if ($owner instanceof Concrete) {
                if ($isUpdate) {
                    // only consider default value for new objects
                    return $data;
                }
                $class = $owner->getClass();
            } elseif ($owner instanceof AbstractData) {
                if ($isUpdate) {
                    // only consider default value for new bricks
                    return $data;
                }
                $class = $owner->getObject()->getClass();
            }

            if ($class && $class->getAllowInherit()) {
                $params = [];

                try {
                    $data = $owner->getValueFromParent($this->getName(), $params);
                    if (!$this->isEmpty($data)) {
                        return $data;
                    }
                } catch (InheritanceParentNotFoundException $e) {
                    // no data from parent available, use the default value
                }
            }
            $data = $this->doGetDefaultValue();
        }

        return $data;
    }
}
