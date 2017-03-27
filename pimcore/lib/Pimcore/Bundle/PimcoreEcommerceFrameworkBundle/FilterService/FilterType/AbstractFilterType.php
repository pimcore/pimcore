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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Config\Config;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractFilterType
{

    const EMPTY_STRING = '$$EMPTY$$';

    protected $script;
    protected $config;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var EngineInterface
     */
    protected $renderer;

    /**
     * @param $script string - script for rendering the filter frontend
     * @param $config Config - for more settings
     * @param $translator TranslatorInterface - translator for text translation im necessary
     * @param $renderer EngineInterface - renderer for view snippet
     */
    public function __construct($script, $config, TranslatorInterface $translator, EngineInterface $renderer)
    {
        $this->script = $script;
        $this->config = $config;
        $this->translator = $translator;
        $this->renderer = $renderer;
    }


    protected function getField(AbstractFilterDefinitionType $filterDefinition)
    {
        $field = $filterDefinition->getField();
        if ($field instanceof IndexFieldSelection) {
            return $field->getField();
        }
        return $field;
    }

    protected function getPreSelect(AbstractFilterDefinitionType $filterDefinition)
    {
        $field = $filterDefinition->getField();
        if ($field instanceof IndexFieldSelection) {
            return $field->getPreSelect();
        } else if (method_exists($filterDefinition, "getPreSelect")) {
            return $filterDefinition->getPreSelect();
        }
        return null;
    }


    /**
     * renders and returns the rendered html snippet for the current filter
     * based on settings in the filter definition and the current filter params.
     *
     * @abstract
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param IProductList $productList
     * @param $currentFilter
     * @return string
     */
    public abstract function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter);

    /**
     * adds necessary conditions to the product list implementation based on the currently set filter params.
     *
     * @abstract
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param IProductList $productList
     * @param $currentFilter
     * @param $params
     * @param bool $isPrecondition
     * @return array
     */
    public abstract function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false);

    /**
     * calls prepareGroupByValues of productlist if necessary
     *
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param IProductList $productList
     */
    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, IProductList $productList)
    {
        //by default do thing here
    }


    /**
     * sort result
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param array $result
     *
     * @return array
     */
    protected function sortResult(AbstractFilterDefinitionType $filterDefinition, array $result)
    {
        return $result;
    }

    /**
     * renders filter template
     *
     * @param $script string
     * @param $parameterBag array
     * @return string
     */
    protected function render($script, $parameterBag)
    {
        try {
            return $this->renderer->render($script, $parameterBag);
        } catch (\Exception $e) {

            //legacy fallback for view rendering
            $prefix = PIMCORE_PROJECT_ROOT . "/legacy/website/views/scripts";
            return $this->renderer->render($prefix . $script, $parameterBag);

        }
    }
}
