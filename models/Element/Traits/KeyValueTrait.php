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

namespace Pimcore\Model\Element\Traits;

trait KeyValueTrait
{
    /**
     * @var array
     */
    protected $_temporaryValues;

    /**
     * @param string $fieldName
     * @param mixed $value
     */
    public function copyValueToTemp($fieldName, $value) {
        $this->_temporaryValues[$fieldName] = $value;
    }

    /**
     * @param string $fieldName
     * @param mixed $matchValue
     * @return bool
     */
    public function matchValueFromTemp($fieldName, $matchValue) {
        $result = false;

        if (isset($this->_temporaryValues[$fieldName]) && $matchValue) {
            $result = $this->_temporaryValues[$fieldName] == $matchValue;
        }

        return $result;
    }
}