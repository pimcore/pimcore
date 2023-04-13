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

namespace Pimcore\Bundle\AdminBundle\Controller;

use Pimcore\Bundle\CoreBundle\Controller\UserAwareController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AdminController extends UserAwareController implements AdminControllerInterface
{
    /**
     * Returns a JsonResponse that uses the admin serializer
     *
     * @param mixed $data    The response data
     * @param int $status    The status code to use for the Response
     * @param array $headers Array of extra headers to add
     * @param array $context Context to pass to serializer when using serializer component
     * @param bool $useAdminSerializer
     *
     * @return JsonResponse
     */
    protected function adminJson($data, $status = 200, $headers = [], $context = [], bool $useAdminSerializer = true)
    {
        $json = $this->encodeJson($data, $context, JsonResponse::DEFAULT_ENCODING_OPTIONS, $useAdminSerializer);

        return new JsonResponse($json, $status, $headers, true);
    }
}
