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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Serializer\Normalizer;

use Pimcore\Tool\Serialize;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ReferenceLoopNormalizer implements NormalizerInterface
{
    /**
     * @inheritDoc
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return Serialize::removeReferenceLoops($object);
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, $format = null)
    {
        return $format === JsonEncoder::FORMAT;
    }
}
