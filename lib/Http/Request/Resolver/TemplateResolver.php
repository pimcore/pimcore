<?php
declare(strict_types=1);

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

namespace Pimcore\Http\Request\Resolver;

use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class TemplateResolver extends AbstractRequestResolver
{
    public function getTemplate(Request $request = null): ?string
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        return $request->attributes->get(DynamicRouter::CONTENT_TEMPLATE);
    }

    public function setTemplate(Request $request, string $template): void
    {
        $request->attributes->set(DynamicRouter::CONTENT_TEMPLATE, $template);
    }
}
