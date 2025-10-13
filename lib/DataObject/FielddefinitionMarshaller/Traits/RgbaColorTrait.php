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

namespace Pimcore\DataObject\FielddefinitionMarshaller\Traits;

/**
 * @internal
 */
trait RgbaColorTrait
{
    public function marshal(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $rgb = sprintf('%02x%02x%02x', $value['r'], $value['g'], $value['b']);
            $a = sprintf('%02x', $value['a']);

            return [
                'value' => $rgb,
                'value2' => $a,
            ];
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $rgb = $value['value'];
            if (!$rgb) {
                return null;
            }
            $a = $value['value2'];
            [$r, $g, $b] = sscanf($rgb, '%02x%02x%02x');
            $a = hexdec($a);

            return [
                'r' => $r,
                'g' => $g,
                'b' => $b,
                'a' => $a,
            ];
        }

        return null;
    }
}
