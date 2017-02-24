<?php

namespace Pimcore\Bundle\PimcoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class Controller extends BaseController
{
    /**
     * Encodes data into JSON string
     *
     * @param mixed $data       The data to serialize
     * @param array $context    Context to pass to serializer when using serializer component
     * @param int $options      Options passed to json_encode
     * @return string
     */
    protected function encodeJson($data, array $context = [], $options = JsonResponse::DEFAULT_ENCODING_OPTIONS)
    {
        if ($this->container->has('serializer')) {
            $json = $this->container->get('serializer')->serialize($data, 'json', array_merge([
                'json_encode_options' => $options
            ], $context));

            return $json;
        }

        return json_encode($data, $options);
    }

    /**
     * Decodes a JSON string into an array/object
     *
     * @param mixed $json           The data to be decoded
     * @param bool  $associative    Whether to decode into associative array or object
     * @param array $context        Context to pass to serializer when using serializer component
     *
     * @return array|\stdClass
     */
    protected function decodeJson($json, $associative = true, array $context = [])
    {
        if ($this->container->has('serializer')) {
            if ($associative) {
                $context['json_decode_associative'] = true;
            }

            return $this->container->get('serializer')->decode($json, 'json', $context);
        }

        return json_decode($json, true);
    }
}
