<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

class ArrayNormalizer
{
    /**
     * @var callable[]
     */
    private $normalizers = [];

    public function normalize(array $array): array
    {
        foreach ($this->normalizers as $property => $normalizer) {
            if (!isset($array[$property])) {
                continue;
            }

            $array[$property] = $normalizer($array[$property], $property, $array);
        }

        return $array;
    }

    public function addNormalizer($properties, callable $normalizer)
    {
        if (!is_array($properties)) {
            $properties = [$properties];
        }

        foreach ($properties as $property) {
            $this->normalizers[$property] = $normalizer;
        }
    }
}
