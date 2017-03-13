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

namespace Pimcore\Bundle\PimcoreBundle\EventListener\Traits;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

trait ResponseInjectionTrait
{
    /**
     * @param Response $response
     * @return bool
     */
    protected function isHtmlResponse(Response $response)
    {
        if ($response instanceof BinaryFileResponse) {
            return false;
        }

        if (strpos($response->getContent(), "<html")) {
            return true;
        }

        return false;
    }
}
