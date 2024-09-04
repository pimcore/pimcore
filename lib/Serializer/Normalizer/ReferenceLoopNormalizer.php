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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Serializer\Normalizer;

use ArrayObject;
use JsonSerializable;
use Pimcore\Tool\Serialize;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
class ReferenceLoopNormalizer implements NormalizerInterface
{
    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|ArrayObject|null
    {
        $object = Serialize::removeReferenceLoops($object);

        if ($object instanceof JsonSerializable) {
            return $object->jsonSerialize();
        }

        if (is_object($object)) {
            $propCollection = get_object_vars($object);

            $array = [];
            foreach ($propCollection as $name => $propValue) {
                $array[$name] = $propValue;
            }

            return $array;
        }

        return $object;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $format === JsonEncoder::FORMAT;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['*' => false];
    }
}
