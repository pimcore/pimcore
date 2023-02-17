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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Debug\Util;

use Pimcore\Bundle\PersonalizationBundle\Targeting\OverrideHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

class OverrideAttributeResolver
{
    public static function setOverrideValue(Request $request, string $key, mixed $value): void
    {
        $overrides = $request->attributes->get(OverrideHandlerInterface::REQUEST_ATTRIBUTE, []);
        $overrides[$key] = $value;

        $request->attributes->set(OverrideHandlerInterface::REQUEST_ATTRIBUTE, $overrides);
    }

    /**
     * @param Request $request
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public static function getOverrideValue(Request $request, string $key, mixed $default = null): mixed
    {
        $overrides = $request->attributes->get(OverrideHandlerInterface::REQUEST_ATTRIBUTE, []);

        return $overrides[$key] ?? $default;
    }
}
