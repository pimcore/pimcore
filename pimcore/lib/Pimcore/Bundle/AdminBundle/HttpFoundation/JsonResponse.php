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

namespace Pimcore\Bundle\AdminBundle\HttpFoundation;

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
    public function setData($data = [])
    {
        $serializer = Serialize::getAdminSerializer();

        $json = $serializer->serialize($data, 'json', [
            'json_encode_options' => $this->encodingOptions
        ]);

        return $this->setJson($json);
    }
}
