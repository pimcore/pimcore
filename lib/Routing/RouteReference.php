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
