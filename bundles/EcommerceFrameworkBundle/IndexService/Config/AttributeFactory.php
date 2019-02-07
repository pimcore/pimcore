<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\Definition\Attribute;
use Psr\Container\ContainerInterface;

/**
 * Builds attributes from config. Getters and interpreters are scoped service locators
 * containing only the configured getters/interpreters.
 */
class AttributeFactory
{
    /**
     * @var ContainerInterface
     */
    private $getters;

    /**
     * @var ContainerInterface
     */
    private $interpreters;

    public function __construct(
        ContainerInterface $getters,
        ContainerInterface $interpreters
    ) {
        $this->getters = $getters;
        $this->interpreters = $interpreters;
    }

    public function createAttribute(array $config): Attribute
    {
        $getter = null;
        if (null !== $getterId = $config['getter_id'] ?? null) {
            $getter = $this->getters->get($getterId);
        }

        $interpreter = null;
        if (null !== $interpreterId = $config['interpreter_id'] ?? null) {
            $interpreter = $this->interpreters->get($interpreterId);
        }

        return new Attribute(
            $config['name'],
            $config['field_name'] ?? null,
            $config['type'] ?? null,
            $config['locale'] ?? null,
            $config['filter_group'] ?? null,
            $config['options'] ?? [],
            $getter,
            $config['getter_options'] ?? [],
            $interpreter,
            $config['interpreter_options'] ?? [],
            $config['hide_in_fieldlist_datatype'] ?? false
        );
    }
}
