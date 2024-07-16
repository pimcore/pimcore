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

namespace Pimcore\Controller\Traits;

use Pimcore\Serializer\Serializer as PimcoreSerializer;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @property ContainerInterface $container
 */
trait JsonHelperTrait
{
    protected PimcoreSerializer $pimcoreSerializer;

    #[Required]
    public function setPimcoreSerializer(PimcoreSerializer $pimcoreSerializer): void
    {
        $this->pimcoreSerializer = $pimcoreSerializer;
    }

    /**
     * Returns a JsonResponse that uses the admin serializer
     *
     * @param mixed $data    The response data
     * @param int $status    The status code to use for the Response
     * @param array $headers Array of extra headers to add
     * @param array $context Context to pass to serializer when using serializer component
     *
     */
    public function jsonResponse(mixed $data, int $status = 200, array $headers = [], array $context = [], bool $usePimcoreSerializer = true): JsonResponse
    {
        $json = $this->encodeJson($data, $context, JsonResponse::DEFAULT_ENCODING_OPTIONS, $usePimcoreSerializer);

        return new JsonResponse($json, $status, $headers, true);
    }

    /**
     * Encodes data into JSON string
     *
     * @param mixed $data    The data to be encoded
     * @param array $context Context to pass to serializer when using serializer component
     * @param int $options   Options passed to json_encode
     */
    public function encodeJson(mixed $data, array $context = [], int $options = JsonResponse::DEFAULT_ENCODING_OPTIONS, bool $usePimcoreSerializer = true): string
    {
        if ($usePimcoreSerializer) {
            $serializer = $this->pimcoreSerializer;
        } else {
            $serializer = $this->container->get('serializer');
        }

        return $serializer->serialize($data, 'json', array_merge([
            'json_encode_options' => $options,
        ], $context));
    }

    /**
     * Decodes a JSON string into an array/object
     *
     * @param mixed $json       The data to be decoded
     * @param bool $associative Whether to decode into associative array or object
     * @param array $context    Context to pass to serializer when using serializer component
     */
    public function decodeJson(mixed $json, bool $associative = true, array $context = [], bool $usePimcoreSerializer = true): mixed
    {
        if ($usePimcoreSerializer) {
            $serializer = $this->pimcoreSerializer;
        } else {
            $serializer = $this->container->get('serializer');
        }

        if ($associative) {
            $context['json_decode_associative'] = true;
        }

        // @phpstan-ignore-next-line
        return $serializer->decode($json, 'json', $context);
    }
}
