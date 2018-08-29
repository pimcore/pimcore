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

namespace Pimcore\Sitemap\Element;

use Pimcore\Model\Element\AbstractElement;
use Pimcore\Sitemap\GeneratorInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;

/**
 * Basic generator for all kinds of elements supporting pluggable filters (= exclude elements) and processors (= enrich
 * generated URL).
 */
abstract class AbstractElementGenerator implements GeneratorInterface
{
    /**
     * @var FilterInterface[]
     */
    private $filters = [];

    /**
     * @var ProcessorInterface[]
     */
    private $processors = [];

    /**
     * @param FilterInterface[] $filters
     * @param ProcessorInterface[] $processors
     */
    public function __construct(array $filters = [], array $processors = [])
    {
        $this->filters = $filters;
        $this->processors = $processors;
    }

    public function addFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @return FilterInterface[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addProcessor(ProcessorInterface $processor)
    {
        $this->processors[] = $processor;
    }

    /**
     * @return ProcessorInterface[]
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    /**
     * Determines if the element can be added.
     *
     * @param AbstractElement $element
     * @param GeneratorContextInterface $context
     *
     * @return bool
     */
    protected function canBeAdded(AbstractElement $element, GeneratorContextInterface $context): bool
    {
        foreach ($this->filters as $filter) {
            if (!$filter->canBeAdded($element, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines if the element handles children (only used from generators
     * supporting tree structures).
     *
     * @param AbstractElement $element
     * @param GeneratorContextInterface $context
     *
     * @return bool
     */
    protected function handlesChildren(AbstractElement $element, GeneratorContextInterface $context): bool
    {
        foreach ($this->filters as $filter) {
            if (!$filter->handlesChildren($element, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Processes a URL about to be added to the sitemap. Can either return an Url instance
     * or null to exclude the Url.
     *
     * @param Url $url
     * @param AbstractElement $element
     * @param GeneratorContextInterface $context
     *
     * @return null|Url
     */
    protected function process(Url $url, AbstractElement $element, GeneratorContextInterface $context)
    {
        foreach ($this->processors as $processor) {
            $url = $processor->process($url, $element, $context);

            // processor returned null - stop processing and return null
            if (null === $url) {
                break;
            }
        }

        return $url;
    }
}
