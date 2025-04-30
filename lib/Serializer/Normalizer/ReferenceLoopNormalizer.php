<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|ArrayObject|null
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

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $format === JsonEncoder::FORMAT;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['*' => false];
    }
}
