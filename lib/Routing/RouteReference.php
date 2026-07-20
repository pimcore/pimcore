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

namespace Pimcore\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
final class RouteReference implements RouteReferenceInterface
{
    protected string $route;

    protected array $parameters;

    protected int $type;

    public function __construct(string $route, array $parameters = [], int $type = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $this->route = $route;
        $this->parameters = $parameters;
        $this->type = $type;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
