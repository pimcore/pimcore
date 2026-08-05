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

interface RouteReferenceInterface
{
    /**
     * Get route name
     *
     */
    public function getRoute(): string;

    /**
     * Get parameters to use when generating the route
     *
     */
    public function getParameters(): array;

    /**
     * Get route type - directly passed to URL generator
     *
     * @see UrlGeneratorInterface
     *
     */
    public function getType(): int;
}
