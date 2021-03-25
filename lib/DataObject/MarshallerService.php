<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject;

use Pimcore\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGeneratorInterface;
use Pimcore\DataObject\FielddefinitionMarshaller\MarshallerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
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
     * @param ContainerInterface $container
     */
    public function __construct($container) {
        $this->container = $container;
    }

    /**
     * @param array $supportedFieldDefinitionMarshallers
     */
    public function setSupportedFieldDefinitionMarshallers($supportedFieldDefinitionMarshallers) {
        $this->supportedFieldDefinitionMarshallers = $supportedFieldDefinitionMarshallers;
    }

    /**
     * @param string $format
     * @param string $name
     * @return \Pimcore\DataObject\MarshallerInterface
     */
    public function buildFieldefinitionMarshaller($format, $name)
    {
        $key = $this->supportedFieldDefinitionMarshallers[$format][$name];
        $result = $this->container->get($key);
        return $result;
    }

    /**
     * @param string $format
     * @param string $name
     * @return bool
     */
    public function supportsFielddefinition(string $format, string $name) {
        return isset($this->supportedFieldDefinitionMarshallers[$format][$name]);
    }
}
