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

namespace Pimcore\FeatureToggles\Features;

use Pimcore\FeatureToggles\Feature;
use Pimcore\FeatureToggles\FeatureContextInterface;
use Pimcore\FeatureToggles\FeatureState;
use Pimcore\FeatureToggles\FeatureStateInitializerInterface;
use Pimcore\FeatureToggles\FeatureStateInterface;
use Pimcore\FeatureToggles\Initializers\ClosureInitializer;

/**
 * @method static DevMode ADMIN()
 * @method static DevMode UPDATES()
 * @method static DevMode UNMINIFIED_JS()
 */
final class DevMode extends Feature
{
    const ADMIN         = 1;
    const UPDATES       = 2;
    const UNMINIFIED_JS = 4;

    public static function getType(): string
    {
        return 'dev_mode';
    }

    public static function getDefaultInitializer(): FeatureStateInitializerInterface
    {
        $initializer = function (FeatureContextInterface $context, FeatureStateInterface $previousState = null) {
            if (null !== $previousState) {
                return $previousState;
            }

            $devMode = false;
            if (defined('PIMCORE_DEVMODE') && PIMCORE_DEVMODE) {
                $devMode = true;
            }

            return FeatureState::fromFeature($devMode ? static::ALL() : static::NONE());
        };

        return new ClosureInitializer(static::getType(), $initializer);
    }
}
