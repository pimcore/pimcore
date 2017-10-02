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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Bundle\EcommerceFrameworkBundle\Traits\OptionsResolverTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @deprecated
 */
class ProxyFilter extends AbstractFilterType
{
    use OptionsResolverTrait;

    /**
     * @var AbstractFilterType
     */
    private $proxy;

    /**
     * @var string
     */
    protected $field;

    protected function processOptions(array $options)
    {
        $options = $this->resolveOptions($options);

        $proxyClass = $options['proxy_class'];
        if (!class_exists($proxyClass)) {
            throw new \InvalidArgumentException(sprintf('Proxy class "%s" does not exist', $proxyClass));
        }

        $this->proxy = new $proxyClass(
            $this->translator,
            $this->templatingEngine,
            $this->template,
            $options['proxy_options']
        );

        $this->field = $options['field'];
    }

    /**
     * @inheritDoc
     */
    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        foreach (['proxy_class', 'field'] as $field) {
            $resolver->setRequired($field);
            $resolver->setAllowedTypes($field, 'string');
        }

        $resolver->setDefaults([
            'proxy_options' => []
        ]);

        $resolver->setAllowedTypes('proxy_options', 'array');
    }

    public function getFilterFrontend(
        AbstractFilterDefinitionType $filterDefinition,
        IProductList $productList,
        $currentFilter
    ) {
        $filterDefinition->field=$this->field;

        return $this->proxy->getFilterFrontend($filterDefinition, $productList, $currentFilter);
    }

    public function addCondition(
        AbstractFilterDefinitionType $filterDefinition,
        IProductList $productList,
        $currentFilter,
        $params,
        $isPrecondition = false
    ) {
        $filterDefinition->field=$this->field;

        return $this->proxy->addCondition($filterDefinition, $productList, $currentFilter, $params, $isPrecondition);
    }
}
