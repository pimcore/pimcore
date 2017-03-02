<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Serializer\Normalizer;

use Pimcore\Tool\Serialize;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ReferenceLoopNormalizer implements NormalizerInterface
{
    /**
     * @inheritDoc
     */
    public function normalize($object, $format = null, array $context = array())
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
