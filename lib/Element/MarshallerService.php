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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Element;

use Pimcore\Marshaller\MarshallerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MarshallerService
{
    /**
     * @var array
     */
    private $supportedFieldDefinitionMarshallers = [];

    /** @var ContainerInterface */
    private $container;

    /**
     * MarshallerService constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param array $supportedFieldDefinitionMarshallers
     */
    public function setSupportedFieldDefinitionMarshallers($supportedFieldDefinitionMarshallers)
    {
        $this->supportedFieldDefinitionMarshallers = $supportedFieldDefinitionMarshallers;
    }

    /**
     * @param string $format
     * @param string $name
     *
     * @return MarshallerInterface
     */
    public function buildFieldefinitionMarshaller($format, $name)
    {
        $key = $this->supportedFieldDefinitionMarshallers[$format . '_' . $name];
        $result = $this->container->get($key);

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
        return isset($this->supportedFieldDefinitionMarshallers[$format . '_' . $name]);
    }
}
