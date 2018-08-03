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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Traits;

use Symfony\Component\OptionsResolver\OptionsResolver;

trait OptionsResolverTrait
{
    /**
     * @var OptionsResolver[]
     */
    protected $optionsResolvers = [];

    /**
     * Runs options through options resolver. Supports multiple options resolvers identified
     * by name (e.g. for sub-options)
     *
     * @param array $options
     * @param string $resolverName
     *
     * @return array
     */
    protected function resolveOptions(array $options, string $resolverName = 'default'): array
    {
        return $this->getOptionsResolver($resolverName)->resolve($options);
    }

    /**
     * Sets up and returns a named options resolver
     *
     * @param string $resolverName
     *
     * @return OptionsResolver
     */
    protected function getOptionsResolver(string $resolverName = 'default'): OptionsResolver
    {
        if (!isset($this->optionsResolvers[$resolverName])) {
            $this->optionsResolvers[$resolverName] = new OptionsResolver();
            $this->configureOptionsResolver($resolverName, $this->optionsResolvers[$resolverName]);
        }

        return $this->optionsResolvers[$resolverName];
    }

    /**
     * Set up options resolver (add defaults, set required fields, ...)
     *
     * @param string $resolverName
     * @param OptionsResolver $resolver
     *
     * @throws \InvalidArgumentException If no resolver with the given name is supported
     */
    abstract protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver);
}
