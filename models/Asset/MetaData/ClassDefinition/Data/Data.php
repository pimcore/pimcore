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
 * @package    Property
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

abstract class Data
{

    /**
     * @param mixed $value
     * @param array $params
     *
     * @return mixed
     */
    public function marshal($value, $params = []) {
        return $value;
    }

    /**
     * @param mixed $value
     * @param array $params
     *
     * @return mixed
     */
    public function unmarshal($value, $params = []) {
        return $value;
    }

    public function __toString() {
        return get_class($this);
    }
}
