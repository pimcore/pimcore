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

namespace Pimcore\Telemetry\Snapshot;

use Pimcore\Extension\Bundle\PimcoreBundleManager;
use function array_map;
use function array_values;
use function count;
use function str_contains;
use function strrchr;
use function substr;

/**
 * The active bundles' short class names, resolved once per snapshot.
 *
 * {@see PimcoreBundleManager::getActiveBundles()} checks each bundle's enabled state individually,
 * so enumerating them costs roughly one statement per installed bundle. Several collectors need the
 * same list, so it is memoized here rather than re-queried by each of them - and the name-shortening
 * and needle-matching they all repeated now live in one place.
 *
 * Content-never: bundle short names are Pimcore's own class identifiers, never customer data.
 *
 * @internal
 */
final class ActiveBundles
{
    /**
     * @var list<string>|null
     */
    private ?array $names = null;

    public function __construct(
        private readonly PimcoreBundleManager $bundleManager,
    ) {
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->names ??= array_values(array_map(
            static function (object $bundle): string {
                $class = $bundle::class;
                $shortName = strrchr($class, '\\');

                return $shortName === false ? $class : substr($shortName, 1);
            },
            $this->bundleManager->getActiveBundles()
        ));
    }

    public function count(): int
    {
        return count($this->names());
    }

    /**
     * Whether any active bundle's short name contains the given needle.
     */
    public function has(string $needle): bool
    {
        foreach ($this->names() as $name) {
            if (str_contains($name, $needle)) {
                return true;
            }
        }

        return false;
    }
}
