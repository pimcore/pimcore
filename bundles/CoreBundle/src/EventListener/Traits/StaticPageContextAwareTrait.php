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
