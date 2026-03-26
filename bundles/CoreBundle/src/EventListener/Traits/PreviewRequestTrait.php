<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\EventListener\Traits;

use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
trait PreviewRequestTrait
{
    protected function isPreviewRequest(Request $request): bool
    {
        if ($request->server->get('HTTP_X_PURPOSE') === 'preview') {
            return true;
        }

        if ($request->server->get('HTTP_PURPOSE') === 'preview') {
            return true;
        }

        if ($request->query->getBoolean('pimcore_preview')) {
            return true;
        }

        return false;
    }
}
