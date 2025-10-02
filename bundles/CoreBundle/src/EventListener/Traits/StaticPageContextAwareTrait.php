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

use Pimcore\Http\Request\Resolver\StaticPageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @internal
 */
trait StaticPageContextAwareTrait
{
    private ?StaticPageResolver $staticPageResolver = null;

    #[Required]
    public function setStaticPageResolver(StaticPageResolver $staticPageResolver): void
    {
        $this->staticPageResolver = $staticPageResolver;
    }

    /**
     * Check if the request has static page context
     *
     *
     */
    protected function matchesStaticPageContext(Request $request): bool
    {
        return $this->staticPageResolver->hasStaticPageContext($request);
    }
}
