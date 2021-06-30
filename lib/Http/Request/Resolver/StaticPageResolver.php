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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Http\Request\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 *
 */
class StaticPageResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_PIMCORE_STATIC_PAGE = '_pimcore_static_page';

    /**
     * @var bool
     */
    protected $timestampWasQueried = false;

    /**
     * {@inheritdoc}
     */
    public function __construct(RequestStack $requestStack)
    {
        parent::__construct($requestStack);
    }

    /**
     *
     * @return bool
     */
    public function hasStaticPageContext($request)
    {
        return $request->attributes->has(self::ATTRIBUTE_PIMCORE_STATIC_PAGE);
    }

    /**
     * @param $request
     */
    public function setStaticPageContext($request)
    {
        $request->attributes->set(self::ATTRIBUTE_PIMCORE_STATIC_PAGE, true);
    }
}
