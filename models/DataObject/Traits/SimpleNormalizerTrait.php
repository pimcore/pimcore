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

namespace Pimcore\Model\DataObject\Traits;

/**
 * @internal
 */
trait SimpleNormalizerTrait
{
    /**
     * @param mixed $value
     * @param array $params
     *
     * @return mixed
     */
    public function normalize($value, $params = [])
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @param array $params
     *
     * @return mixed
     */
    public function denormalize($value, $params = [])
    {
        return $value;
    }
}
