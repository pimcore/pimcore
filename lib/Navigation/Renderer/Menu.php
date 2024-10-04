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

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_Navigation_Menu
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Navigation\Renderer;

use Exception;
use Pimcore\Navigation\Container;
use Pimcore\Navigation\Page;
use RecursiveIteratorIterator;

class Menu extends AbstractRenderer
{
    /**
     * CSS class to use for the ul element
     *
     */
    protected string $_ulClass = 'navigation';

    /**
     * Unique identifier (id) for the ul element
     *
     */
    protected ?string $_ulId = null;

    /**
     * CSS class to use for the active elements
     *
     */
    protected string $_activeClass = 'active';

    /**
     * CSS class to use for the parent li element
     *
     */
    protected string $_parentClass = 'menu-parent';

    /**
     * Whether parent li elements should be rendered with parent class
     *
     */
    protected bool $_renderParentClass = false;

    /**
     * Whether only active branch should be rendered
     *
     */
    protected bool $_onlyActiveBranch = false;

    /**
     * Whether parents should be rendered when only rendering active branch
     *
     */
    protected bool $_renderParents = true;

    /**
     * Partial view script to use for rendering menu
     *
     */
    protected string|array|null $_template = null;

    /**
     * Expand all sibling nodes of active branch nodes
     *
     */
    protected bool $_expandSiblingNodesOfActiveBranch = false;

    /**
     * Adds CSS class from page to li element
     *
     */
    protected bool $_addPageClassToLi = false;

    /**
     * Inner indentation string
     *
     */
    protected string $_innerIndent = '    ';

    // Accessors:

    /**
     * Sets CSS class to use for the first 'ul' element when rendering
     *
     * @param string $ulClass                   CSS class to set
     *
     * @return $this
     */
    public function setUlClass(string $ulClass): static
    {
        $this->_ulClass = $ulClass;

        return $this;
    }

    /**
     * Returns CSS class to use for the first 'ul' element when rendering
     *
     * @return string  CSS class
     */
    public function getUlClass(): string
    {
        return $this->_ulClass;
    }

    /**
     * Sets unique identifier (id) to use for the first 'ul' element when
     * rendering
     *
     * @param string|null $ulId                Unique identifier (id) to set
     *
     * @return $this
     */
    public function setUlId(?string $ulId): static
    {
        if (is_string($ulId)) {
            $this->_ulId = $ulId;
        }

        return $this;
    }

    /**
     * Returns unique identifier (id) to use for the first 'ul' element when
     * rendering
     *
     * @return string|null  Unique identifier (id); Default is 'null'
     */
    public function getUlId(): ?string
    {
        return $this->_ulId;
    }

    /**
     * Sets CSS class to use for the active elements when rendering
     *
     * @param string $activeClass               CSS class to set
     *
     * @return $this
     */
    public function setActiveClass(string $activeClass): static
    {
        $this->_activeClass = $activeClass;

        return $this;
    }

    /**
     * Returns CSS class to use for the active elements when rendering
     *
     * @return string  CSS class
     */
    public function getActiveClass(): string
    {
        return $this->_activeClass;
    }

    /**
     * Sets CSS class to use for the parent li elements when rendering
     *
     * @param string $parentClass              CSS class to set to parents
     *
     * @return $this
     */
    public function setParentClass(string $parentClass): static
    {
        $this->_parentClass = $parentClass;

        return $this;
    }

    /**
     * Returns CSS class to use for the parent lie elements when rendering
     *
     * @return string CSS class
     */
    public function getParentClass(): string
    {
        return $this->_parentClass;
    }

    /**
     * Enables/disables rendering of parent class to the li element
     *
     * @param bool $flag                        [optional] render with parent
     *                                          class. Default is true.
     *
     * @return $this
     */
    public function setRenderParentClass(bool $flag = true): static
    {
        $this->_renderParentClass = $flag;

        return $this;
    }

    /**
     * Returns flag indicating whether parent class should be rendered to the li
     * element
     *
     * @return bool  whether parent class should be rendered
     */
    public function getRenderParentClass(): bool
    {
        return $this->_renderParentClass;
    }

    /**
     * Sets a flag indicating whether only active branch should be rendered
     *
     * @param bool $flag                        [optional] render only active
     *                                           branch. Default is true.
     *
     * @return $this
     */
    public function setOnlyActiveBranch(bool $flag = true): static
    {
        $this->_onlyActiveBranch = $flag;

        return $this;
    }

    /**
     * Returns a flag indicating whether only active branch should be rendered
     *
     * By default, this value is false, meaning the entire menu will be
     * be rendered.
     *
     * @return bool  whether only active branch should be rendered
     */
    public function getOnlyActiveBranch(): bool
    {
        return $this->_onlyActiveBranch;
    }

    /**
     * Sets a flag indicating whether to expand all sibling nodes of the active branch
     *
     * @param bool $flag                        [optional] expand all siblings of
     *                                           nodes in the active branch. Default is true.
     *
     * @return $this
     */
    public function setExpandSiblingNodesOfActiveBranch(bool $flag = true): static
    {
        $this->_expandSiblingNodesOfActiveBranch = $flag;

        return $this;
    }

    /**
     * Returns a flag indicating whether to expand all sibling nodes of the active branch
     *
     * By default, this value is false, meaning the entire menu will be
     * be rendered.
     *
     * @return bool  whether siblings of nodes in the active branch should be expanded
     */
    public function getExpandSiblingNodesOfActiveBranch(): bool
    {
        return $this->_expandSiblingNodesOfActiveBranch;
    }

    /**
     * Enables/disables rendering of parents when only rendering active branch
     *
     * See {@link setOnlyActiveBranch()} for more information.
     *
     * @param bool $flag                        [optional] render parents when
     *                                           rendering active branch.
     *                                           Default is true.
     *
     * @return $this
     */
    public function setRenderParents(bool $flag = true): static
    {
        $this->_renderParents = $flag;

        return $this;
    }

    /**
     * Returns flag indicating whether parents should be rendered when rendering
     * only the active branch
     *
     * By default, this value is true.
     *
     * @return bool  whether parents should be rendered
     */
    public function getRenderParents(): bool
    {
        return $this->_renderParents;
    }

    public function getTemplate(): array|string|null
    {
        return $this->_template;
    }

    public function setTemplate(array|string $template): void
    {
        $this->_template = $template;
    }

    /**
     * Alias of setTemplate()
     *
     *
     * @return $this
     */
    public function setPartial(array|string $partial): static
    {
        $this->_template = $partial;

        return $this;
    }

    /**
     * Alias of getTemplate()
     *
     */
    public function getPartial(): array|string|null
    {
        return $this->_template;
    }

    /**
     * Adds CSS class from page to li element
     *
     * Before:
     * <code>
     * <li>
     *     <a href="#" class="foo">Bar</a>
     * </li>
     * </code>
     *
     * After:
     * <code>
     * <li class="foo">
     *     <a href="#">Bar</a>
     * </li>
     * </code>
     *
     * @param bool $flag                        [optional] adds CSS class from
     *                                          page to li element
     *
     * @return $this
     */
    public function addPageClassToLi(bool $flag = true): static
    {
        $this->_addPageClassToLi = $flag;

        return $this;
    }

    /**
     * Returns a flag indicating whether the CSS class from page to be added to
     * li element
     *
     */
    public function getAddPageClassToLi(): bool
    {
        return $this->_addPageClassToLi;
    }

    /**
     * Set the inner indentation string for using in {@link render()}, optionally
     * a number of spaces to indent with
     *
     * @param int|string $indent                          indentation string or
     *                                                     number of spaces
     *
     * @return $this  fluent interface,
     *                                                     returns self
     */
    public function setInnerIndent(int|string $indent): static
    {
        $this->_innerIndent = $this->_getWhitespace($indent);

        return $this;
    }

    /**
     * Returns inner indentation (format output is respected)
     *
     * @return string       indentation string or an empty string
     */
    public function getInnerIndent(): string
    {
        return $this->_innerIndent;
    }

    // Public methods:

    /**
     * Returns an HTML string containing an 'a' element for the given page if
     * the page's href is not empty, and a 'span' element if it is empty
     *
     * @param  Page $page  page to generate HTML for
     *
     * @return string                      HTML string for the given page
     */
    public function htmlify(Page $page): string
    {
        $label = $page->getLabel();
        $title = $page->getTitle();

        // get attribs for element
        $attribs = [
            'id' => $page->getId(),
            'title' => $title,
        ];

        if (false === $this->getAddPageClassToLi()) {
            $attribs['class'] = $page->getClass();
        }

        // does page have a href?
        if ($href = $page->getHref()) {
            $element = 'a';
            $attribs['href'] = $href;
            $attribs['target'] = $page->getTarget();
            $attribs['accesskey'] = $page->getAccessKey();
        } else {
            $element = 'span';
        }

        // Add custom HTML attributes
        $attribs = array_merge($attribs, $page->getCustomHtmlAttribs());

        return '<' . $element . $this->_htmlAttribs($attribs) . '>'
             . htmlspecialchars((string) $label, ENT_COMPAT, 'UTF-8')
             . '</' . $element . '>';
    }

    /**
     * Normalizes given render options
     *
     * @param  array $options  [optional] options to normalize
     *
     * @return array           normalized options
     */
    protected function _normalizeOptions(array $options = []): array
    {
        // Ident
        if (isset($options['indent'])) {
            $options['indent'] = $this->_getWhitespace($options['indent']);
        } else {
            $options['indent'] = $this->getIndent();
        }

        // Inner ident
        if (isset($options['innerIndent'])) {
            $options['innerIndent'] =
                $this->_getWhitespace($options['innerIndent']);
        } else {
            $options['innerIndent'] = $this->getInnerIndent();
        }

        // UL class
        if (isset($options['ulClass']) && $options['ulClass'] !== null) {
            $options['ulClass'] = $options['ulClass'];
        } else {
            $options['ulClass'] = $this->getUlClass();
        }

        // UL id
        if (isset($options['ulId']) && $options['ulId'] !== null) {
            $options['ulId'] = (string) $options['ulId'];
        } else {
            $options['ulId'] = $this->getUlId();
        }

        // Active class
        if (isset($options['activeClass']) && $options['activeClass'] !== null
        ) {
            $options['activeClass'] = (string) $options['activeClass'];
        } else {
            $options['activeClass'] = $this->getActiveClass();
        }

        // Parent class
        if (isset($options['parentClass']) && $options['parentClass'] !== null) {
            $options['parentClass'] = (string) $options['parentClass'];
        } else {
            $options['parentClass'] = $this->getParentClass();
        }

        // Minimum depth
        if (array_key_exists('minDepth', $options)) {
            if (null !== $options['minDepth']) {
                $options['minDepth'] = (int) $options['minDepth'];
            }
        } else {
            $options['minDepth'] = $this->getMinDepth();
        }

        if ($options['minDepth'] < 0 || $options['minDepth'] === null) {
            $options['minDepth'] = 0;
        }

        // Maximum depth
        if (array_key_exists('maxDepth', $options)) {
            if (null !== $options['maxDepth']) {
                $options['maxDepth'] = (int) $options['maxDepth'];
            }
        } else {
            $options['maxDepth'] = $this->getMaxDepth();
        }

        // Only active branch
        if (!isset($options['onlyActiveBranch'])) {
            $options['onlyActiveBranch'] = $this->getOnlyActiveBranch();
        }

        // Expand sibling nodes of active branch
        if (!isset($options['expandSiblingNodesOfActiveBranch'])) {
            $options['expandSiblingNodesOfActiveBranch'] = $this->getExpandSiblingNodesOfActiveBranch();
        }

        // Render parents?
        if (!isset($options['renderParents'])) {
            $options['renderParents'] = $this->getRenderParents();
        }

        // Render parent class?
        if (!isset($options['renderParentClass'])) {
            $options['renderParentClass'] = $this->getRenderParentClass();
        }

        // Add page CSS class to LI element
        if (!isset($options['addPageClassToLi'])) {
            $options['addPageClassToLi'] = $this->getAddPageClassToLi();
        }

        return $options;
    }

    // Render methods:

    /**
     * Renders the deepest active menu within [$minDepth, $maxDeth], (called
     * from {@link renderMenu()})
     *
     * @param  Container $container     container to render
     * @param string $ulClass       CSS class for first UL
     * @param string $indent        initial indentation
     * @param string $innerIndent   inner indentation
     * @param int|null $minDepth      minimum depth
     * @param int|null $maxDepth      maximum depth
     * @param string|null $ulId          unique identifier (id)
     *                                                  for first UL
     * @param bool $addPageClassToLi  adds CSS class from
     *                                                      page to li element
     * @param string|null $activeClass       CSS class for active
     *                                                      element
     * @param string $parentClass       CSS class for parent
     *                                                      li's
     * @param bool $renderParentClass Render parent class?
     *
     * @return string                                       rendered menu (HTML)
     */
    protected function _renderDeepestMenu(
        Container $container,
        string $ulClass,
        string $indent,
        string $innerIndent,
        ?int $minDepth,
        ?int $maxDepth,
        ?string $ulId,
        bool $addPageClassToLi,
        ?string $activeClass,
        string $parentClass,
        bool $renderParentClass
    ): string {
        if (!$active = $this->findActive($container, $minDepth - 1, $maxDepth)) {
            return '';
        }

        // special case if active page is one below minDepth
        if ($active['depth'] < $minDepth) {
            if (!$active['page']->hasPages()) {
                return '';
            }
        } elseif (!$active['page']->hasPages()) {
            // found pages has no children; render siblings
            $active['page'] = $active['page']->getParent();
        } elseif (is_int($maxDepth) && $active['depth'] + 1 > $maxDepth) {
            // children are below max depth; render siblings
            $active['page'] = $active['page']->getParent();
        }

        $attribs = [
            'class' => $ulClass,
            'id' => $ulId,
        ];

        // We don't need a prefix for the menu ID (backup)
        $skipValue = $this->_skipPrefixForId;
        $this->skipPrefixForId();

        $html = $indent . '<ul'
                        . $this->_htmlAttribs($attribs)
                        . '>'
                        . $this->getEOL();

        // Reset prefix for IDs
        $this->_skipPrefixForId = $skipValue;

        /** @var Page $subPage */
        foreach ($active['page'] as $subPage) {
            if (!$this->accept($subPage)) {
                continue;
            }

            $liClass = '';
            if ($subPage->isActive(true) && $addPageClassToLi) {
                $liClass = $this->_htmlAttribs(
                    ['class' => $activeClass . ' ' . $subPage->getClass()]
                );
            } elseif ($subPage->isActive(true)) {
                $liClass = $this->_htmlAttribs(['class' => $activeClass]);
            } elseif ($addPageClassToLi) {
                $liClass = $this->_htmlAttribs(
                    ['class' => $subPage->getClass()]
                );
            }
            $html .= $indent . $innerIndent . '<li' . $liClass . '>' . $this->getEOL();
            $html .= $indent . str_repeat($innerIndent, 2) . $this->htmlify($subPage)
                                                           . $this->getEOL();
            $html .= $indent . $innerIndent . '</li>' . $this->getEOL();
        }

        $html .= $indent . '</ul>';

        return $html;
    }

    /**
     * Renders a normal menu (called from {@link renderMenu()})
     *
     * @param  Container                 $container     container to render
     * @param string|string[] $ulClasses     CSS class for UL levels
     * @param string $indent        initial indentation
     * @param string $innerIndent   inner indentation
     * @param int|null $minDepth      minimum depth
     * @param int|null $maxDepth      maximum depth
     * @param bool $onlyActive    render only active branch?
     * @param bool $expandSibs    render siblings of active
     *                                                  branch nodes?
     * @param string|null $ulId          unique identifier (id)
     *                                                  for first UL
     * @param bool $addPageClassToLi  adds CSS class from
     *                                                      page to li element
     * @param string|null $activeClass       CSS class for active
     *                                                      element
     * @param string $parentClass       CSS class for parent
     *                                                      li's
     * @param bool $renderParentClass Render parent class?
     *
     * @return string                                       rendered menu (HTML)
     */
    protected function _renderMenu(
        Container $container,
        array|string $ulClasses,
        string $indent,
        string $innerIndent,
        ?int $minDepth,
        ?int $maxDepth,
        bool $onlyActive,
        bool $expandSibs,
        ?string $ulId,
        bool $addPageClassToLi,
        ?string $activeClass,
        string $parentClass,
        bool $renderParentClass
    ): string {
        $html = '';

        // find deepest active
        if ($found = $this->findActive($container, $minDepth, $maxDepth)) {
            $foundPage = $found['page'];
            $foundDepth = $found['depth'];
        } else {
            $foundPage = null;
            $foundDepth = null;
        }

        // create iterator
        $iterator = new RecursiveIteratorIterator($container, RecursiveIteratorIterator::SELF_FIRST);

        if (is_int($maxDepth)) {
            $iterator->setMaxDepth($maxDepth);
        }

        // iterate container
        $prevDepth = -1;
        foreach ($iterator as $page) {
            $depth = $iterator->getDepth();
            $isActive = $page->isActive(true);

            // Set ulClass depth wise if array of classes is supplied.
            if (is_array($ulClasses)) {
                $ulClass = $ulClasses[$depth] ?? $ulClasses['default'];
            } else {
                $ulClass = (string) $ulClasses;
            }

            if ($depth < $minDepth || !$this->accept($page)) {
                // page is below minDepth or not accepted by visibilty
                continue;
            } elseif ($expandSibs && $depth > $minDepth) {
                // page is not active itself, but might be in the active branch
                $accept = false;
                if ($foundPage) {
                    if ($foundPage->hasPage($page)) {
                        // accept if page is a direct child of the active page
                        $accept = true;
                    } elseif ($page->getParent()->isActive(true)) {
                        // page is a sibling of the active branch...
                        $accept = true;
                    }
                }
                if (!$isActive && !$accept) {
                    continue;
                }
            } elseif ($onlyActive && !$isActive) {
                // page is not active itself, but might be in the active branch
                $accept = false;
                if ($foundPage) {
                    if ($foundPage->hasPage($page)) {
                        // accept if page is a direct child of the active page
                        $accept = true;
                    } elseif ($foundPage->getParent()->hasPage($page)) {
                        // page is a sibling of the active page...
                        if (!$foundPage->hasPages() ||
                            is_int($maxDepth) && $foundDepth + 1 > $maxDepth) {
                            // accept if active page has no children, or the
                            // children are too deep to be rendered
                            $accept = true;
                        }
                    }
                }

                if (!$accept) {
                    continue;
                }
            }

            // make sure indentation is correct
            $depth -= $minDepth;
            $myIndent = $indent . str_repeat($innerIndent, $depth * 2);

            if ($depth > $prevDepth) {
                $attribs = [];

                // start new ul tag
                if ((is_string($ulClasses) && 0 == $depth) || is_array($ulClasses)) {
                    $attribs = [
                        'class' => $ulClass,
                        'id' => $ulId,
                    ];
                }

                // We don't need a prefix for the menu ID (backup)
                $skipValue = $this->_skipPrefixForId;
                $this->skipPrefixForId();

                $html .= $myIndent . '<ul'
                                   . $this->_htmlAttribs($attribs)
                                   . '>'
                                   . $this->getEOL();

                // Reset prefix for IDs
                $this->_skipPrefixForId = $skipValue;
            } elseif ($prevDepth > $depth) {
                // close li/ul tags until we're at current depth
                for ($i = $prevDepth; $i > $depth; $i--) {
                    $ind = $indent . str_repeat($innerIndent, $i * 2);
                    $html .= $ind . $innerIndent . '</li>' . $this->getEOL();
                    $html .= $ind . '</ul>' . $this->getEOL();
                }
                // close previous li tag
                $html .= $myIndent . $innerIndent . '</li>' . $this->getEOL();
            } else {
                // close previous li tag
                $html .= $myIndent . $innerIndent . '</li>' . $this->getEOL();
            }

            // render li tag and page
            $liClasses = [];
            // Is page active?
            if ($isActive) {
                $liClasses[] = $activeClass;
            }
            // Add CSS class from page to LI?
            if ($addPageClassToLi) {
                $liClasses[] = $page->getClass();
            }
            // Add CSS class for parents to LI?
            if ($renderParentClass && $page->hasVisiblePages()) {
                // Check max depth
                if ((is_int($maxDepth) && ($depth + 1 < $maxDepth))
                    || !is_int($maxDepth)
                ) {
                    $liClasses[] = $parentClass;
                }
            }

            $html .= $myIndent . $innerIndent . '<li'
                   . $this->_htmlAttribs(['class' => implode(' ', $liClasses)])
                   . '>' . $this->getEOL()
                   . $myIndent . str_repeat($innerIndent, 2)
                   . $this->htmlify($page)
                   . $this->getEOL();

            // store as previous depth for next iteration
            $prevDepth = $depth;
        }

        if ($html) {
            // done iterating container; close open ul/li tags
            for ($i = $prevDepth + 1; $i > 0; $i--) {
                $myIndent = $indent . str_repeat($innerIndent . $innerIndent, $i - 1);
                $html .= $myIndent . $innerIndent . '</li>' . $this->getEOL()
                         . $myIndent . '</ul>' . $this->getEOL();
            }
            $html = rtrim($html, $this->getEOL());
        }

        return $html;
    }

    /**
     * Renders helper
     *
     * Renders a HTML 'ul' for the given $container. If $container is not given,
     * the container registered in the helper will be used.
     *
     * Available $options:
     *
     * @param  array $options    [optional] options for controlling rendering
     *
     * @return string rendered menu
     */
    public function renderMenu(Container $container, array $options = []): string
    {
        $options = $this->_normalizeOptions($options);

        if ($options['onlyActiveBranch'] && !$options['renderParents']) {
            $html = $this->_renderDeepestMenu(
                $container,
                $options['ulClass'],
                $options['indent'],
                $options['innerIndent'],
                $options['minDepth'],
                $options['maxDepth'],
                $options['ulId'],
                $options['addPageClassToLi'],
                $options['activeClass'],
                $options['parentClass'],
                $options['renderParentClass']
            );
        } else {
            $html = $this->_renderMenu(
                $container,
                $options['ulClass'],
                $options['indent'],
                $options['innerIndent'],
                $options['minDepth'],
                $options['maxDepth'],
                $options['onlyActiveBranch'],
                $options['expandSiblingNodesOfActiveBranch'],
                $options['ulId'],
                $options['addPageClassToLi'],
                $options['activeClass'],
                $options['parentClass'],
                $options['renderParentClass']
            );
        }

        return $html;
    }

    /**
     * Renders the inner-most sub menu for the active page in the $container
     *
     * This is a convenience method which is equivalent to the following call:
     * <code>
     * renderMenu($container, array(
     *     'indent'           => $indent,
     *     'ulClass'          => $ulClass,
     *     'minDepth'         => null,
     *     'maxDepth'         => null,
     *     'onlyActiveBranch' => true,
     *     'renderParents'    => false
     * ));
     * </code>
     *
     * @param string|null $ulClass    [optional] CSS class to
     *                                               use for UL element. Default
     *                                               is to use the value from
     *                                               {@link getUlClass()}.
     * @param int|string|null $indent     [optional] indentation as
     *                                               a string or number of
     *                                               spaces. Default is to use
     *                                               the value retrieved from
     *                                               {@link getIndent()}.
     * @param string|null $ulId       [optional] Unique identifier
     *                                               (id) use for UL element
     * @param bool $addPageClassToLi  adds CSS class from
     *                                                      page to li element
     * @param int|string|null $innerIndent   [optional] inner
     *                                                  indentation as a string
     *                                                  or number of spaces.
     *                                                  Default is to use the
     *                                                  {@link getInnerIndent()}.
     *
     * @return string                                   rendered content
     */
    public function renderSubMenu(
        Container $container,
        string $ulClass = null,
        int|string $indent = null,
        string $ulId = null,
        bool $addPageClassToLi = false,
        int|string $innerIndent = null
    ): string {
        return $this->renderMenu($container, [
            'indent' => $indent,
            'innerIndent' => $innerIndent,
            'ulClass' => $ulClass,
            'minDepth' => null,
            'maxDepth' => null,
            'onlyActiveBranch' => true,
            'renderParents' => false,
            'ulId' => $ulId,
            'addPageClassToLi' => $addPageClassToLi,
        ]);
    }

    /**
     * Renders the given $container with the given template
     *
     * The container will simply be passed on as a model to the view script
     * as-is, and will be available in the partial script as 'container', e.g.
     * <code>echo 'Number of pages: ', count($this->container);</code>.
     *
     * @param array|string|null $partial     [optional] partial view
     *                                               script to use. Default is to
     *                                               use the partial registered
     *                                               in the helper. If an array
     *                                               is given, it is expected to
     *                                               contain two values; the
     *                                               partial view script to use,
     *                                               and the module where the
     *                                               script can be found.
     *
     * @return string                                helper output
     *
     * @throws Exception   When no partial script is set
     */
    public function renderTemplate(Container $container, array|string $partial = null): string
    {
        if (null === $partial) {
            $partial = $this->getTemplate();
        }

        if (empty($partial)) {
            $e = new Exception('Unable to render menu: No partial view script provided');

            throw $e;
        }

        $model = [
            'pages' => $container, // we can't use `container` as index name, since this is used by the DI-container
        ];

        return $this->templatingEngine->render($partial, $model);
    }

    /**
     * Alias of renderTemplate()
     *
     *
     */
    public function renderPartial(Container $container, array|string $partial = null): string
    {
        return $this->renderTemplate($container, $partial);
    }

    public function render(Container $container): string
    {
        if ($partial = $this->getTemplate()) {
            return $this->renderTemplate($container, $partial);
        } else {
            return $this->renderMenu($container);
        }
    }
}
