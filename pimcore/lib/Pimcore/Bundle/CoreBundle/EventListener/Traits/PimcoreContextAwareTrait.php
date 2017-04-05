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

use Pimcore\Service\Request\PimcoreContextResolver;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;

trait PimcoreContextAwareTrait
{
    /**
     * @var PimcoreContextResolver
     */
    protected $pimcoreContextResolver;

    /**
     * @param PimcoreContextResolver $contextResolver
     */
    public function setPimcoreContextResolver(PimcoreContextResolver $contextResolver)
    {
        $this->pimcoreContextResolver = $contextResolver;
    }

    /**
     * Check if the request matches the given pimcore context (e.g. admin)
     *
     * @param Request $request
     * @param string|array $context
     * @return bool
     */
    protected function matchesPimcoreContext(Request $request, $context)
    {
        if (null === $this->pimcoreContextResolver) {
            throw new RuntimeException('Missing pimcore context resolver. Is the listener properly configured?');
        }

        if (!is_array($context)) {
            if (!empty($context)) {
                $context = [$context];
            } else {
                $context = [];
            }
        }

        if (empty($context)) {
            throw new \InvalidArgumentException('Can\'t match against empty pimcore context');
        }

        $resolvedContext = $this->pimcoreContextResolver->getPimcoreContext($request);
        if (!$resolvedContext) {
            // no context available to match -> false
            return false;
        }

        foreach ($context as $ctx) {
            if ($ctx === $resolvedContext) {
                return true;
            }
        }

        return false;
    }
}
