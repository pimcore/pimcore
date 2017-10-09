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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\IndexService;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\Definition\Attribute;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttributeFactory
{
    /**
     * @var OptionsResolver
     */
    protected $optionsResolver;

    public function __construct()
    {
        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);

        $this->optionsResolver = $optionsResolver;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('name');
        $resolver->setAllowedTypes('name', 'string');

        $resolver->setDefaults([
            'field_name' => null,
            'type' => null,
            'locale' => null,
            'filter_group' => null,
            'options' => [],
            'getter_id' => null,
            'getter_options' => [],
            'interpreter_id' => null,
            'interpreter_options' => [],
            'hide_in_fieldlist_datatype' => false,
            'mapping' => null
        ]);

        foreach (['field_name', 'type', 'locale', 'filter_group', 'getter_id', 'interpreter_id'] as $stringOption) {
            $resolver->setAllowedTypes($stringOption, ['string', 'null']);
        }

        foreach (['options', 'getter_options', 'interpreter_options', 'mapping'] as $arrayOption) {
            $resolver->setAllowedTypes($arrayOption, 'array');
        }

        $resolver->setAllowedTypes('hide_in_fieldlist_datatype', 'bool');
    }

    /**
     * @param array $config
     *
     * @return Definition[]
     */
    public function createAttributes(array $config): array
    {
        $attributes = [];
        foreach ($config as $attributeConfig) {
            $attributes[] = $this->createAttribute($attributeConfig);
        }

        return $attributes;
    }

    public function createAttribute(array $config): Definition
    {
        $options = $this->optionsResolver->resolve($config);

        $getter = null;
        if ($options['getter_id']) {
            $getter = new Reference($options['getter_id']);
        }

        $interpreter = null;
        if ($options['interpreter_id']) {
            $interpreter = new Reference($options['interpreter_id']);
        }

        $attribute = new Definition(Attribute::class, [
            $options['name'],
            $options['field_name'],
            $options['type'],
            $options['locale'],
            $options['filter_group'],
            $options['options'],
            $getter,
            $options['getter_options'],
            $interpreter,
            $options['interpreter_options'],
            $options['hide_in_fieldlist_datatype'],
            $options['mapping'],
        ]);

        return $attribute;
    }
}
