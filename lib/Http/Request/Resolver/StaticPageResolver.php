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

namespace Pimcore\Http\Request\Resolver;

use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 */
class StaticPageResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_PIMCORE_STATIC_PAGE = '_pimcore_static_page';

    public function hasStaticPageContext(Request $request): bool
    {
        return $request->attributes->has(self::ATTRIBUTE_PIMCORE_STATIC_PAGE);
    }

    public function setStaticPageContext(Request $request): void
    {
        $request->attributes->set(self::ATTRIBUTE_PIMCORE_STATIC_PAGE, true);
    }
}
