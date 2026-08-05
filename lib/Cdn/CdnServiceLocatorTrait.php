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

namespace Pimcore\Cdn;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * Shared resolution policy for the CDN service registries (purge client, image transform
 * adapter): resolve from a tag-keyed PSR-11 locator, map an empty env selection to the
 * 'null' no-op implementation, and warn-and-degrade to 'null' when the configured key is
 * not registered. Keeping the policy in one place guarantees the purge path and the
 * image-optimizer path behave identically on misconfiguration.
 */
trait CdnServiceLocatorTrait
{
    /**
     * @param ContainerInterface $locator     Locator keyed by the service tag attribute; must
     *                                        contain a 'null' entry (the no-op implementation).
     * @param string             $selectedKey The env-selected key; '' selects 'null'.
     * @param string             $logMessage  Warning logged when a non-empty key is not registered.
     * @param array<string, string> $logContext
     */
    private function resolveFromLocator(
        ContainerInterface $locator,
        string $selectedKey,
        LoggerInterface $logger,
        string $logMessage,
        array $logContext,
    ): object {
        // Empty selection maps to the 'null' key (no-op implementation), not an empty-string key.
        $key = $selectedKey === '' ? 'null' : $selectedKey;

        if (!$locator->has($key)) {
            if ($key !== 'null') {
                $logger->warning($logMessage, $logContext);
            }
            $key = 'null';
        }

        return $locator->get($key);
    }
}
