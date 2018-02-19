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
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * @method static DebugMode SYMFONY_ENVIRONMENT()
 * @method static DebugMode SYMFONY_KERNEL_DEBUG()
 * @method static DebugMode MAGIC_PARAMS()
 * @method static DebugMode EXCEPTION_TRACES()
 * @method static DebugMode ERROR_REPORTING()
 * @method static DebugMode NO_ERROR_PAGE()
 * @method static DebugMode REST_ERRORS()
 * @method static DebugMode RENDER_DOCUMENT_TAG_ERRORS()
 * @method static DebugMode MAIL()
 * @method static DebugMode LOG()
 * @method static DebugMode UPDATE()
 * @method static DebugMode DISABLE_HTTP_CACHE()
 * @method static DebugMode DISABLE_FULL_PAGE_CACHE()
 * @method static DebugMode TARGETING()
 */
final class DebugMode extends Feature
{
    const SYMFONY_ENVIRONMENT        = 1;
    const SYMFONY_KERNEL_DEBUG       = 2;
    const MAGIC_PARAMS               = 4;
    const EXCEPTION_TRACES           = 8;
    const ERROR_REPORTING            = 16;
    const NO_ERROR_PAGE              = 32;
    const REST_ERRORS                = 64;
    const RENDER_DOCUMENT_TAG_ERRORS = 128;
    const MAIL                       = 256;
    const LOG                        = 512;
    const UPDATE                     = 1024;
    const DISABLE_HTTP_CACHE         = 2048;
    const DISABLE_FULL_PAGE_CACHE    = 4096;
    const TARGETING                  = 8192;

    public static function getType(): string
    {
        return 'debug_mode';
    }

    public static function getDefaultInitializer(): FeatureStateInitializerInterface
    {
        $initializer = function (FeatureContextInterface $context, FeatureStateInterface $previousState = null) {
            if (null !== $previousState) {
                return $previousState;
            }

            $debug = false;

            if (defined('PIMCORE_DEBUG')) {
                $debug = true;
            } else {
                $debugModeFile = PIMCORE_CONFIGURATION_DIRECTORY . '/debug-mode.php';

                if (file_exists($debugModeFile)) {
                    $conf  = include $debugModeFile;
                    $debug = $conf['active'];

                    // enable debug mode only for a comma-separated list of IP addresses/ranges
                    if ($debug && $conf['ip']) {
                        $debug = false;

                        $clientIp = Tool::getClientIp();
                        if (null !== $clientIp) {
                            $debugIpAddresses = explode_and_trim(',', $conf['ip']);

                            if (IpUtils::checkIp($clientIp, $debugIpAddresses)) {
                                $debug = true;
                            }
                        }
                    }
                }
            }

            if ($debug) {
                $request = Tool::resolveRequest();
                if ($request && (bool)$request->cookies->get('pimcore_disable_debug')) {
                    $debug = false;
                }
            }

            return FeatureState::fromFeature($debug ? static::ALL() : static::NONE());
        };

        return new ClosureInitializer(static::getType(), $initializer);
    }
}
