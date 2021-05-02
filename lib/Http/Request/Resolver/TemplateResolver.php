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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Http\Request\Resolver;

use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class TemplateResolver extends AbstractRequestResolver
{
    /**
     * @param Request $request
     *
     * @return null|string
     */
    public function getTemplate(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        $template = $request->get(DynamicRouter::CONTENT_TEMPLATE, null);

        return $template;
    }

    /**
     * @param Request $request
     * @param string $template
     */
    public function setTemplate(Request $request, $template)
    {
        $request->attributes->set(DynamicRouter::CONTENT_TEMPLATE, $template);
    }
}
