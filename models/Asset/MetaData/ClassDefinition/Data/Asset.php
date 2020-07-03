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

use Pimcore\Model\Element\Service;

class Asset extends Data
{
    /**
     * @param mixed $value
     * @param array $params
     *
     * @return null|int
     */
    public function marshal($value, $params = [])
    {
        $element = Service::getElementByPath('asset', $value);
        if ($element) {
            return $element->getId();
        } else {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param array $params
     *
     * @return string
     */
    public function unmarshal($value, $params = [])
    {
        $element = null;
        if (is_numeric($value)) {
            $element = Service::getElementById('asset', $value);
        }
        if ($element) {
            $value = $element->getRealFullPath();
        } else {
            $value = '';
        }

        return $value;
    }
}
