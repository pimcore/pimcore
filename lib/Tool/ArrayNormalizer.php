<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tool;

/**
 * @internal
 */
class ArrayNormalizer
{
    /**
     * @var callable[]
     */
    private array $normalizers = [];

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

    /**
     * @param int|string|int[]|string[] $properties
     *
     */
    public function addNormalizer(array|int|string $properties, callable $normalizer): void
    {
        if (!is_array($properties)) {
            $properties = [$properties];
        }

        foreach ($properties as $property) {
            $this->normalizers[$property] = $normalizer;
        }
    }
}
