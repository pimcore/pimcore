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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\FactFinder;

use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @deprecated since version 6.7.0 and will be removed in 7.0.0.
 *
 */
class MultiSelect extends \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelect
{
    /**
     * @param TranslatorInterface $translator
     * @param EngineInterface $templatingEngine
     * @param string $template for rendering the filter frontend
     * @param array $options for additional options
     */
    public function __construct(TranslatorInterface $translator, EngineInterface $templatingEngine, RequestStack $requestStack, string $template, array $options = [])
    {
        @trigger_error(
            'Class ' . self::class . ' is deprecated since version 6.7.0 and will be removed in 7.0.0.',
            E_USER_DEPRECATED
        );

        parent::__construct($translator, $templatingEngine, $requestStack, $template, $options);
    }

    /**
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param ProductListInterface                 $productList
     */
    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList)
    {
    }

    /**
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param ProductListInterface                 $productList
     * @param array                                             $currentFilter
     * @param array                                             $params
     * @param bool                                              $isPrecondition
     *
     * @return mixed
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter, $params, $isPrecondition = false)
    {
        // init
        $field = $this->getField($filterDefinition);
        $value = $params[$field] ?? null;
        $isReload = $params['is_reload'] ?? null;

        // set defaults
        if (empty($value) && !$isReload && ($preSelect = $this->getPreSelect($filterDefinition))) {
            $value = explode(',', $preSelect);
        } elseif (!empty($value) && in_array(AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

        $currentFilter[$field] = $value;

        if (!empty($value)) {
            $quotedValues = [];

            foreach ($value as $v) {
                if (!empty($v)) {
                    $quotedValues[] = $v;
                }
            }

            if (!empty($quotedValues)) {
                if ($filterDefinition->getUseAndCondition()) {
                    foreach ($quotedValues as $value) {
                        $productList->addCondition($value, $field);
                    }
                } else {
                    $productList->addCondition($quotedValues, $field);
                }
            }
        }

        return $currentFilter;
    }
}
