<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\HttpFoundation;

use Pimcore\Tool\Serialize;
use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;

/**
 * This response serializes its data through the admin serializer (with reference loop handling) instead
 * of calling json_encode. This is to make sure we have consistent responses with `$this->json()` and `new JsonResponse`
 * in admin controllers.
 */
class JsonResponse extends BaseJsonResponse
{
    /**
     * @inheritDoc
     */
    public function setData($data = array())
    {
        $serializer = Serialize::getAdminSerializer();

        $json = $serializer->serialize($data, 'json', [
            'json_encode_options' => $this->encodingOptions
        ]);

        return $this->setJson($json);
    }
}
