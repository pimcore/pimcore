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

namespace Pimcore\Service\Request;

use Pimcore\Service\MvcConfigNormalizer;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TemplateResolver extends AbstractRequestResolver
{
    /**
     * @var MvcConfigNormalizer
     */
    protected $configNormalizer;

    /**
     * @param RequestStack $requestStack
     * @param MvcConfigNormalizer $configNormalizer
     */
    public function __construct(RequestStack $requestStack, MvcConfigNormalizer $configNormalizer)
    {
        parent::__construct($requestStack);

        $this->configNormalizer = $configNormalizer;
    }

    /**
     * @param Request $request
     * @return null|string
     */
    public function getTemplate(Request $request = null)
    {
        if (null === $request) {
            $request = $this->getCurrentRequest();
        }

        $template = $request->get(DynamicRouter::CONTENT_TEMPLATE, null);
        $template = $this->configNormalizer->normalizeTemplate($template);

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
