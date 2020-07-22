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

class Date extends Data
{
    /**
     * @param mixed $value
     * @param array $params
     *
     * @return null|string
     */
    public function marshal($value, $params = [])
    {
        if ($value && !is_numeric($value)) {
            $value = strtotime($value);
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param array $params
     */
    public function getVersionPreview($value, $params = [])
    {
        return date('m/d/Y', $value);
    }
}
