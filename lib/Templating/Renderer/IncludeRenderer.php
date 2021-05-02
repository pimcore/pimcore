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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Templating\Renderer;

use Pimcore\Cache;
use Pimcore\Model;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Element;
use Pimcore\Targeting\Document\DocumentTargetingConfigurator;
use Pimcore\Tool\DeviceDetector;
use Pimcore\Tool\DomCrawler;
use Pimcore\Tool\Frontend;

/**
 * @internal
 */
class IncludeRenderer
{
    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

    /**
     * @var DocumentTargetingConfigurator
     */
    private $targetingConfigurator;

    public function __construct(
        ActionRenderer $actionRenderer,
        DocumentTargetingConfigurator $targetingConfigurator
    ) {
        $this->actionRenderer = $actionRenderer;
        $this->targetingConfigurator = $targetingConfigurator;
    }

    /**
     * Renders a document include
     *
     * @param mixed $include
     * @param array $params
     * @param bool $editmode
     * @param bool $cacheEnabled
     *
     * @return string
     */
    public function render($include, array $params = [], $editmode = false, $cacheEnabled = true)
    {
        if (!is_array($params)) {
            $params = [];
        }

        $originalInclude = $include;

        // this is if $this->inc is called eg. with $this->relation() as argument
        if (!$include instanceof PageSnippet && is_object($include) && method_exists($include, '__toString')) {
            $include = (string)$include;
        }

        if (is_numeric($include)) {
            try {
                $include = Model\Document::getById($include);
            } catch (\Exception $e) {
                $include = $originalInclude;
            }
        } elseif (is_string($include)) {
            try {
                $include = Model\Document::getByPath($include);
            } catch (\Exception $e) {
                $include = $originalInclude;
            }
        }

        if ($include instanceof PageSnippet && $include->isPublished()) {
            // apply best matching target group (if any)
            $this->targetingConfigurator->configureTargetGroup($include);
        }

        // check if output-cache is enabled, if so, we're also using the cache here
        $cacheKey = null;
        $cacheConfig = false;

        if ($cacheEnabled && !$editmode) {
            if ($cacheConfig = Frontend::isOutputCacheEnabled()) {
                // cleanup params to avoid serializing Element\ElementInterface objects
                $cacheParams = $params;
                $cacheParams['~~include-document'] = $originalInclude;

                array_walk($cacheParams, function (&$value, $key) {
                    if ($value instanceof Element\ElementInterface) {
                        $value = $value->getId();
                    } elseif (is_object($value) && method_exists($value, '__toString')) {
                        $value = (string)$value;
                    }
                });

                // TODO is this enough for cache or should we disable caching completely?
                if ($include instanceof TargetingDocumentInterface && $include->getUseTargetGroup()) {
                    $cacheParams['target_group'] = $include->getUseTargetGroup();
                }

                $cacheKey = 'tag_inc__' . md5(serialize($cacheParams));
                if ($content = Cache::load($cacheKey)) {
                    return $content;
                }
            }
        }

        $params = array_merge($params, ['document' => $include]);
        $content = '';

        if ($include instanceof PageSnippet && $include->isPublished()) {
            $content = $this->renderAction($include, $params);

            if ($editmode) {
                $content = $this->modifyEditmodeContent($include, $content);
            }
        }

        // write contents to the cache, if output-cache is enabled & not in editmode
        if ($cacheConfig && !$editmode && !DeviceDetector::getInstance()->wasUsed()) {
            Cache::save($content, $cacheKey, ['output', 'output_inline'], $cacheConfig['lifetime']);
        }

        return $content;
    }

    /**
     * @param PageSnippet $include
     * @param array $params
     *
     * @return string
     */
    protected function renderAction(PageSnippet $include, $params)
    {
        return $this->actionRenderer->render($include, $params);
    }

    /**
     * in editmode, we need to parse the returned html from the document include
     * add a class and the pimcore id / type so that it can be opened in editmode using the context menu
     * if there's no first level HTML container => add one (wrapper)
     *
     * @param PageSnippet $include
     * @param string $content
     *
     * @return string
     */
    protected function modifyEditmodeContent(PageSnippet $include, $content)
    {
        $editmodeClass = ' pimcore_editable pimcore_editable_inc ';

        // this is if the content that is included does already contain markup/html
        // this is needed by the editmode to highlight included documents
        try {
            $html = new DomCrawler($content);
            $childs = $html->filterXPath('//' . DomCrawler::FRAGMENT_WRAPPER_TAG . '/*'); // FRAGMENT_WRAPPER_TAG is added by DomCrawler for fragments
            /** @var \DOMElement $child */
            foreach ($childs as $child) {
                $child->setAttribute('class', $child->getAttribute('class') . $editmodeClass);
                $child->setAttribute('pimcore_type', $include->getType());
                $child->setAttribute('pimcore_id', $include->getId());
            }
            $content = $html->html();

            $html->clear();
            unset($html);
        } catch (\Exception $e) {
            // add a div container if the include doesn't contain markup/html
            $content = '<div class="' . $editmodeClass . '" pimcore_id="' . $include->getId() . '" pimcore_type="' . $include->getType() . '">' . $content . '</div>';
        }

        return $content;
    }
}
