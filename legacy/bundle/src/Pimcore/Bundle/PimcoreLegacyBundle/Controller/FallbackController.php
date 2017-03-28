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

namespace Pimcore\Bundle\PimcoreLegacyBundle\Controller;

use Pimcore\Service\Request\PimcoreContextResolver;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class FallbackController extends Controller
{
    public function fallbackAction(Request $request)
    {
        $resolver = $this->get('pimcore.service.request.pimcore_context_resolver');

        $invalidContexts = [
            PimcoreContextResolver::CONTEXT_ADMIN,
            PimcoreContextResolver::CONTEXT_WEBSERVICE
        ];

        if (in_array($resolver->getPimcoreContext($request), $invalidContexts)) {
            // TODO enable this when all admin modules were migrated
            // throw $this->createNotFoundException('Legacy mode is not supported for admin controllers');
        }

        $legacyKernel = $this->get('pimcore.legacy_kernel');

        return $legacyKernel->handle($request);
    }
}
