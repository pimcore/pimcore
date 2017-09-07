<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\CoreBundle\EventListener\Traits;

use Pimcore\Http\ResponseHelper;
use Symfony\Component\HttpFoundation\Response;

trait ResponseInjectionTrait
{
    /**
     * @var ResponseHelper
     */
    private $responseHelper;

    /**
     * @required
     *
     * @param ResponseHelper $responseHelper
     */
    public function setResponseHelper(ResponseHelper $responseHelper)
    {
        $this->responseHelper = $responseHelper;
    }

    /**
     * @param Response $response
     *
     * @return bool
     */
    protected function isHtmlResponse(Response $response): bool
    {
        return $this->responseHelper->isHtmlResponse($response);
    }
}
