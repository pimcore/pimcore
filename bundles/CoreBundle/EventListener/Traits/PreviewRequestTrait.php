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

namespace Pimcore\Bundle\CoreBundle\EventListener\Traits;

use Symfony\Component\HttpFoundation\Request;

trait PreviewRequestTrait
{
    /**
     * @param Request $request
     *
     * @return bool
     */
    protected function isPreviewRequest(Request $request)
    {
        if ($request->server->get('HTTP_X_PURPOSE') === 'preview') {
            return true;
        }

        if ($request->server->get('HTTP_PURPOSE') === 'preview') {
            return true;
        }

        if ($request->get('pimcore_preview') === 'true') {
            return true;
        }

        return false;
    }
}
