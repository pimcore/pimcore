<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType;

use Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFilterType
{
    const EMPTY_STRING = '$$EMPTY$$';

    protected TranslatorInterface $translator;

    protected EngineInterface $templatingEngine;

    protected string $template;

    protected ?Request $request = null;

    /**
     * @param string $template for rendering the filter frontend
     * @param array $options for additional options
     */
    public function __construct(
        TranslatorInterface $translator,
        EngineInterface $templatingEngine,
        RequestStack $requestStack,
        string $template,
        array $options = []
    ) {
        $this->translator = $translator;
        $this->templatingEngine = $templatingEngine;
        $this->template = $template;
        $this->request = $requestStack->getCurrentRequest();

        $this->processOptions($options);
    }

    protected function processOptions(array $options): void
    {
        // noop - to implemented by filter types supporting options
    }

    protected function getField(AbstractFilterDefinitionType $filterDefinition): string|IndexFieldSelection|null
    {
        $field = $filterDefinition->getField();
        if ($field instanceof IndexFieldSelection) {
            return $field->getField();
        }

        return $field;
    }

    protected function getTemplate(AbstractFilterDefinitionType $filterDefinition): ?string
    {
        $template = $this->template;
        if (!empty($filterDefinition->getScriptPath())) {
            $template = $filterDefinition->getScriptPath();
        }

        return $template;
    }

    protected function getPreSelect(AbstractFilterDefinitionType $filterDefinition): array|string|null
    {
        $field = $filterDefinition->getField();
        if ($field instanceof IndexFieldSelection) {
            return $field->getPreSelect();
        } elseif (method_exists($filterDefinition, 'getPreSelect')) {
            return $filterDefinition->getPreSelect();
        }

        return null;
    }

    /**
     * renders and returns the rendered html snippet for the current filter
     * based on settings in the filter definition and the current filter params.
     *
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     *
     * @return string
     */
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter): string
    {
        return $this->render(
            $this->getTemplate($filterDefinition),
            $this->getFilterValues($filterDefinition, $productList, $currentFilter)
        );
    }

    /**
     * returns the raw data for the current filter based on settings in the
     * filter definition and the current filter params.
     *
     * @abstract
     *
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     *
     * @return array
     */
    abstract public function getFilterValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter): array;

    /**
     * adds necessary conditions to the product list implementation based on the currently set filter params.
     *
     * @abstract
     *
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     * @param array $params
     * @param bool $isPrecondition
     *
     * @return array
     *
     * @throws InvalidConfigException
     */
    abstract public function addCondition(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter, array $params, bool $isPrecondition = false): array;

    /**
     * calls prepareGroupByValues of productlist if necessary
     *
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param ProductListInterface $productList
     *
     */
    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList): void
    {
        //by default do thing here
    }

    /**
     * sort result
     *
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param array $result
     *
     * @return array
     */
    protected function sortResult(AbstractFilterDefinitionType $filterDefinition, array $result): array
    {
        return $result;
    }

    /**
     * renders filter template
     *
     * @param string $template
     * @param array $parameters
     *
     * @return string
     */
    protected function render(string $template, array $parameters = []): string
    {
        return $this->templatingEngine->render($template, $parameters);
    }
}
