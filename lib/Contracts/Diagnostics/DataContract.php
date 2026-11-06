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

namespace Pimcore\Contracts\Diagnostics;

use Attribute;
use InvalidArgumentException;
use function is_string;
use function sprintf;

/**
 * Declares which data a diagnostic check delivers. Core only validates its shape; a consumer of the checks may
 * enforce it further.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DataContract
{
    /**
     * @param list<DataClass> $returns Data classes the check delivers; must not be empty
     * @param list<string> $neverReturns Fields or kinds of value present in the source that must never be returned
     * @param list<string> $sources Where the check reads from (table, command, service, config key)
     * @param string $doc Anchor or relative path (with anchor) into the documentation of the providing bundle, or a URL; not validated here
     */
    public function __construct(
        public array $returns,
        public array $neverReturns = [],
        public array $sources = [],
        public string $doc = '',
    ) {
        if ($returns === []) {
            throw new InvalidArgumentException('"returns" must contain at least one DataClass case.');
        }

        foreach ($returns as $class) {
            if (!$class instanceof DataClass) {
                throw new InvalidArgumentException('Every entry of "returns" must be a DataClass case.');
            }
        }

        foreach (['neverReturns' => $neverReturns, 'sources' => $sources] as $name => $entries) {
            foreach ($entries as $entry) {
                if (!is_string($entry) || $entry === '') {
                    throw new InvalidArgumentException(sprintf('Every entry of "%s" must be a non-empty string.', $name));
                }
            }
        }
    }
}
