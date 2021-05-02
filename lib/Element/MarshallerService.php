<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Element;

use Pimcore\Marshaller\MarshallerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class MarshallerService
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ServiceLocator
     */
    private $marshallerLocator;

    /**
     * @param ContainerInterface $container
     */
    public function __construct($container, ServiceLocator $marshallerLocator)
    {
        $this->container = $container;
        $this->marshallerLocator = $marshallerLocator;
    }

    /**
     * @param string $format
     * @param string $name
     *
     * @return MarshallerInterface
     */
    public function buildFieldefinitionMarshaller($format, $name)
    {
        $result = $this->marshallerLocator->get($format . '_' . $name);

        return $result;
    }

    /**
     * @param string $format
     * @param string $name
     *
     * @return bool
     */
    public function supportsFielddefinition(string $format, string $name)
    {
        $supported = $this->marshallerLocator->has($format . '_' . $name);

        return $supported;
    }
}
