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

namespace Pimcore\Bundle\CoreBundle\Request;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @internal
 */
final class DeprecatedRenderletAttributeBag extends ParameterBag
{
    public function __construct(array $parameters, private readonly array $deprecated)
    {
        parent::__construct($parameters);
    }

    #[\Override]
    public function all(?string $key = null): array
    {
        if (null !== $key && in_array($key, $this->deprecated, true)) {
            trigger_deprecation('pimcore/pimcore', '2026.3', 'Fetching the custom "%s" renderlet parameter from "attributes" is deprecated and will be removed in Pimcore 2027.0. Fetch it from "query" instead.', $key);
        }

        return parent::all($key);
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        if (in_array($key, $this->deprecated, true)) {
            trigger_deprecation('pimcore/pimcore', '2026.3', 'Fetching the custom "%s" renderlet parameter from "attributes" is deprecated and will be removed in Pimcore 2027.0. Fetch it from "query" instead.', $key);
        }

        return parent::get($key, $default);
    }

    #[\Override]
    public function has(string $key): bool
    {
        if (in_array($key, $this->deprecated, true)) {
            trigger_deprecation('pimcore/pimcore', '2026.3', 'Fetching the custom "%s" renderlet parameter from "attributes" is deprecated and will be removed in Pimcore 2027.0. Fetch it from "query" instead.', $key);
        }

        return parent::has($key);
    }
}
