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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ContainerAwareInterface should be implemented by classes that depend on a Container.
 *
 * This interface is compatible with Symfony 6's ContainerAwareInterface which was
 * removed in Symfony 7. It provides backward compatibility for Pimcore applications
 * that need to maintain support for both Symfony 6 and 7.
 *
 * Note: This interface is deprecated by design. New code should use dependency injection
 * instead of depending on the service container directly.
 *
 * @internal
 */
interface ContainerAwareInterface
{
    /**
     * Sets the container.
     */
    public function setContainer(?ContainerInterface $container): void;
}
