<?php

declare(strict_types=1);

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

namespace Pimcore\Targeting\Debug\Util;

use Pimcore\Targeting\OverrideHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

class OverrideAttributeResolver
{
    public static function setOverrideValue(Request $request, string $key, $value)
    {
        $overrides = $request->attributes->get(OverrideHandlerInterface::REQUEST_ATTRIBUTE, []);
        $overrides[$key] = $value;

        $request->attributes->set(OverrideHandlerInterface::REQUEST_ATTRIBUTE, $overrides);
    }

    public static function getOverrideValue(Request $request, string $key, $default = null)
    {
        $overrides = $request->attributes->get(OverrideHandlerInterface::REQUEST_ATTRIBUTE, []);

        return $overrides[$key] ?? $default;
    }
}
