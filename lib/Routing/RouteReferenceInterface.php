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
